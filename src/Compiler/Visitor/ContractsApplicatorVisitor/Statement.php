<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler\Visitor
 */
abstract class Statement implements \Stringable
{
    /**
     * @var Expression
     */
    protected Expression $ast;

    /**
     * @psalm-taint-sink eval $expression
     * @psalm-taint-sink file $file
     *
     * @param Expr $ast
     * @param non-empty-string $expression
     * @param string|null $reason
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        Expr $ast,
        protected readonly string $expression,
        protected readonly ?string $reason,
        protected readonly string $file,
        protected readonly int $line
    ) {
        $this->ast = $this->wrap($ast);
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->ast;
    }

    /**
     * @param Expr $expr
     * @return Expression
     */
    abstract protected function wrap(Expr $expr): Expression;

    /**
     * @param Expr $expr
     * @param FullyQualified $exception
     * @return Expression
     */
    protected function ternary(Expr $expr, FullyQualified $exception): Expression
    {
        return new Expression(
            new Expr\Ternary($expr, null, new Expr\Throw_(
                new Expr\New_($exception, [
                    new Arg(new String_($this->reason ?: $this->expression)),
                    new Arg(new String_($this->file)),
                    new Arg(new LNumber($this->line)),
                ])
            ))
        );
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
