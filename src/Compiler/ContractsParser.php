<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;
use PhpParser\Parser as ParserInterface;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Compiler\Statement\EnsureStatement;
use Serafim\Contracts\Compiler\Statement\InvariantStatement;
use Serafim\Contracts\Compiler\Statement\Statement;
use Serafim\Contracts\Compiler\Statement\VerifyStatement;
use Serafim\Contracts\Exception\SpecificationException;

final class ContractsParser
{
    /**
     * @var non-empty-string
     */
    private const ERROR_EMPTY_EXPRESSION = '%s expression cannot be empty';

    /**
     * @param ParserInterface $parser
     */
    public function __construct(
        private readonly ParserInterface $parser,
    ) {
    }

    /**
     * @psalm-taint-sink file $file
     * @psalm-taint-sink eval $expression
     * @param non-empty-string $file
     * @param non-empty-string $expression
     * @param class-string $type
     * @param NodeAbstract $node
     * @return Expr
     */
    private function parse(string $file, string $expression, string $type, NodeAbstract $node): Expr
    {
        try {
            /** @var Expression[] $ast */
            $ast = $this->parser->parse('<?php ' . $expression . ';');

            if (\count($ast) === 0) {
                throw new \LogicException(\sprintf(self::ERROR_EMPTY_EXPRESSION, $type));
            }
        } catch (Error $e) {
            $line = $node->getLine() + $e->getStartLine() - 1;
            throw SpecificationException::create($e->getRawMessage(), $file, $line);
        } catch (\Throwable $e) {
            throw SpecificationException::create($e->getMessage(), $file, $node->getLine());
        }

        return $ast[0]->expr;
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @param class-string $attr
     * @param class-string $stmt
     * @return list<Statement>
     */
    private function statements(string $file, Attribute $node, string $attr, string $stmt): iterable
    {
        foreach ($node->args as $argument) {
            $value = $argument->value;

            if (! $value instanceof Node\Scalar\String_) {
                throw SpecificationException::badType($attr, $file, $value->getStartLine());
            }

            $expression = $this->parse($file, $value->value, $attr, $node);

            yield new $stmt($expression, $value->value, $file, $node->getLine());
        }
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<InvariantStatement>
     */
    public function invariant(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Invariant::class, InvariantStatement::class);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<EnsureStatement>
     */
    public function ensure(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Ensure::class, EnsureStatement::class);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<VerifyStatement>
     */
    public function verify(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Verify::class, VerifyStatement::class);
    }
}
