<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

use PhpParser\Comment;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\EnsureStatement;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\InvariantStatement;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\VerifyStatement;
use Serafim\Contracts\Compiler\Visitor\ReturnDecoratorVisitor;
use Serafim\Contracts\Exception\SpecificationException;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class MethodInjector
{
    /**
     * @var string
     */
    private const ERROR_OLD_INSIDE_STATIC = 'Could not use "$old" variable inside static method %s()';

    /**
     * @param ContractsParser $parser
     */
    public function __construct(
        private readonly ContractsParser $parser
    ) {
    }

    /**
     * @param non-empty-string $file
     * @param ClassMethod $method
     * @param list<InvariantStatement> $invariants
     * @return ClassMethod
     * @throws \Exception
     */
    public function inject(string $file, ClassMethod $method, array $invariants): ClassMethod
    {
        $isResultUsed = $isOldStateUsed = false;
        $preconditions = $postconditions = [];

        foreach ($this->getPreconditions($file, $method) as $precondition) {
            $preconditions[] = $precondition->getExpression();
        }

        $old = $this->generateVariable('old');
        $result = $this->generateVariable('result');

        /** @var Attribute $ensure */
        foreach ($this->getPostconditions($file, $method) as $ensure => $postcondition) {
            $finder = new NodeFinder();

            /** @var Variable $variable */
            foreach ($finder->findInstanceOf([$postcondition->getExpression()], Variable::class) as $variable) {
                switch ($variable->name) {
                    case 'result':
                        $isResultUsed = true;
                        $variable->name = $result->name;
                        break;

                    case 'old':
                        // Check that method is not static
                        if (($method->flags & Stmt\Class_::MODIFIER_STATIC) === Stmt\Class_::MODIFIER_STATIC) {
                            $message = \sprintf(self::ERROR_OLD_INSIDE_STATIC, $method->name->toString());
                            throw SpecificationException::create($message, $file, $ensure->getLine());
                        }

                        $isOldStateUsed = true;
                        $variable->name = $old->name;
                        break;
                }
            }

            $postconditions[] = $postcondition->getExpression();
        }

        // Has Ensure Statements
        if (\count($postconditions)) {
            $preconditions = [];

            // Add clone expression in case of "$old" variable has been used.
            if ($isOldStateUsed) {
                $cloneExpression = new Expression(
                    new Assign($old, new Clone_(new Variable('this')))
                );

                \array_unshift($preconditions, $cloneExpression);
            }

            // Add result variable initialization in case of "$result"
            // variable has been used.
            if ($isResultUsed) {
                $resultExpression = new Expression(
                    new Assign($result, new ConstFetch(new Name('null')))
                );

                \array_unshift($preconditions, $resultExpression);
            }

            // Decorate return
            $method->stmts = $this->wrapReturnStatement($result, $method->stmts);
        }

        foreach ($invariants as $invariant) {
            $postconditions[] = $invariant->getExpression();
        }

        if ($method->stmts !== null) {
            $method->stmts = $this->getDecorator($method->stmts, $preconditions, $postconditions);
        }

        return $method;
    }

    /**
     * @param string $text
     * @return array{comments: array<Comment>}
     */
    private function comment(string $text): array
    {
        return ['comments' => [
            new Comment('/* ' . \str_replace('*/', '*\\/', $text) . ' */')
        ]];
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param ClassMethod $method
     * @return list<VerifyStatement>
     */
    private function getPreconditions(string $file, ClassMethod $method): iterable
    {
        foreach ($this->getAttributes($method, Verify::class) as $attribute) {
            yield from $this->parser->verify($file, $attribute);
        }
    }

    /**
     * @psalm-template T
     *
     * @param ClassMethod $method
     * @param class-string<T> $needle
     * @return list<T>
     */
    private function getAttributes(ClassMethod $method, string $needle): iterable
    {
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                if ($attr->name->toString() === $needle) {
                    yield $attr;
                }
            }
        }
    }

    /**
     * @param non-empty-string $prefix
     * @return Variable
     * @throws \Exception
     */
    private function generateVariable(string $prefix): Variable
    {
        return new Variable('__⁠' . $prefix . '⁠' . \hash('xxh32', \random_bytes(32)));
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param ClassMethod $method
     * @return EnsureStatement[]
     */
    private function getPostconditions(string $file, ClassMethod $method): iterable
    {
        foreach ($this->getAttributes($method, Ensure::class) as $attribute) {
            foreach ($this->parser->ensure($file, $attribute) as $item) {
                yield $attribute => $item;
            }
        }
    }

    /**
     * @param Variable $result
     * @param iterable<Stmt> $method
     * @return iterable<Stmt>
     */
    private function wrapReturnStatement(Variable $result, iterable $method): iterable
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ReturnDecoratorVisitor($result));

        return $traverser->traverse($method);
    }

    /**
     * @param array $body
     * @param array $preconditions
     * @param array $postconditions
     * @return array
     */
    private function getDecorator(array $body, array $preconditions, array $postconditions): array
    {
        $result = $preconditions;

        if (\count($postconditions)) {
            $result[] = new TryCatch($body, [], new Finally_($postconditions));
        } else {
            foreach ($body as $stmt) {
                $result[] = $stmt;
            }
        }

        return $result;
    }
}
