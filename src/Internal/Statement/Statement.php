<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal\Statement;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;

/**
 * @internal Statement is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
abstract class Statement
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var int
     */
    protected $line;

    /**
     * @var string
     */
    protected $expression;

    /**
     * @var Expression
     */
    protected $ast;

    /**
     * @param Expr $ast
     * @param string $expression
     * @param string $file
     * @param int $line
     */
    public function __construct(Expr $ast, string $expression, string $file, int $line)
    {
        $this->expression = $expression;
        $this->file = $file;
        $this->line = $line;

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
}