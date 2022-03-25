<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Compiler\Statement\EnsureStatement;
use Serafim\Contracts\Compiler\Statement\InvariantStatement;
use Serafim\Contracts\Compiler\Statement\VerifyStatement;
use Serafim\Contracts\Compiler\Visitor\ReturnDecoratorVisitor;

final class MethodInjector
{
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
        $preconditions = $postconditions = [];

        foreach ($this->getPreconditions($file, $method) as $precondition) {
            $preconditions[] = $precondition->getExpression();
        }

        $old = $this->generateVariable('old');
        $result = $this->generateVariable('result');

        foreach ($this->getPostconditions($file, $method) as $postcondition) {
            $postconditions[] = $this->modifyPostcondition($old, $result, $postcondition->getExpression());
        }

        // Has Ensure Statements
        if (\count($postconditions)) {
            // Add clone expression
            \array_unshift($preconditions, new Expression(
                new Assign($old, new Clone_(new Variable('this')))
            ));

            // Decorate return
            $this->wrapReturnStatement($result, $method);
        }

        foreach ($invariants as $invariant) {
            $postconditions[] = $invariant->getExpression();
        }

        $method->stmts = $this->getDecorator($method->stmts, $preconditions, $postconditions);

        return $method;
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
        return new Variable('__' . $prefix . \hash('xxh64', \random_bytes(32)));
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
            yield from $this->parser->ensure($file, $attribute);
        }
    }

    /**
     * @param Variable $old
     * @param Variable $result
     * @param Expression $expr
     * @return Expression
     */
    private function modifyPostcondition(Variable $old, Variable $result, Expression $expr): Expression
    {
        $finder = new NodeFinder();

        /** @var Variable $variable */
        foreach ($finder->findInstanceOf([$expr], Variable::class) as $variable) {
            switch ($variable->name) {
                case 'result':
                    $variable->name = $result->name;
                    break;

                case 'old':
                    $variable->name = $old->name;
                    break;
            }
        }

        return $expr;
    }

    /**
     * @param Variable $result
     * @param ClassMethod $method
     */
    private function wrapReturnStatement(Variable $result, ClassMethod $method): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ReturnDecoratorVisitor($result));
        $traverser->traverse($method->stmts);
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
