<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal\Compiler;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;
use PhpParser\Parser;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Exception\SpecificationException;
use Serafim\Contracts\Internal\Statement\EnsureStatement;
use Serafim\Contracts\Internal\Statement\InvariantStatement;
use Serafim\Contracts\Internal\Statement\Statement;
use Serafim\Contracts\Internal\Statement\VerifyStatement;

/**
 * @internal ContractsParser is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class ContractsParser
{
    /**
     * @var string
     */
    private const ERROR_EMPTY_EXPRESSION = '%s expression cannot be empty';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $file
     * @param string $expression
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
     * @param string $file
     * @param Attribute $node
     * @param class-string $attr
     * @param class-string $stmt
     * @return Statement[]
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
     * @param string $file
     * @param Attribute $node
     * @return InvariantStatement[]
     */
    public function invariant(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Invariant::class, InvariantStatement::class);
    }

    /**
     * @param string $file
     * @param Attribute $node
     * @return EnsureStatement[]
     */
    public function ensure(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Ensure::class, EnsureStatement::class);
    }

    /**
     * @param string $file
     * @param Attribute $node
     * @return VerifyStatement[]
     */
    public function verify(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Verify::class, VerifyStatement::class);
    }
}