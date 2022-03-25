<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Statement;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression;

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
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        Expr $ast,
        protected readonly string $expression,
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
     * @param Expr $expression
     * @return Expression
     */
    abstract protected function wrap(Expr $expression): Expression;

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
