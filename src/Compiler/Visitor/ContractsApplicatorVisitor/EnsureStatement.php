<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor;

use PhpParser\Node\Expr;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Expression;
use Serafim\Contracts\Exception\PostconditionException;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler\Visitor
 */
final class EnsureStatement extends Statement
{
    /**
     * @param Expr $expr
     * @return Expression
     */
    protected function wrap(Expr $expr): Expression
    {
        return $this->ternary($expr, new FullyQualified(PostconditionException::class));
    }
}
