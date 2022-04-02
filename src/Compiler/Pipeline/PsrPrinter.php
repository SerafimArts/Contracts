<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Pipeline;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

final class PsrPrinter extends Standard
{
    /**
     * <code>
     * // Before
     * declare ( ... );
     *
     * // After
     * declare( ... );
     * </code>
     */
    protected function pStmt_Declare(Stmt\Declare_ $node): string
    {
        $result = 'declare(' . $this->pCommaSeparated($node->declares) . ')';

        if ($node->stmts === null) {
            return $result . ';';
        }

        return $result . ' {'
            . $this->pStmts($node->stmts) . $this->nl
        . '}';
    }

    /**
     * <code>
     * // Before
     * public function some() : void;
     *
     * // After
     * public function some(): void;
     * </code>
     */
    protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string
    {
        $result = $this->pAttrGroups($node->attrGroups)
            . $this->pModifiers($node->flags)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pMaybeMultiline($node->params) . ')';

        if ($node->returnType !== null) {
            $result .= ': ' . $this->p($node->returnType);
        }

        if ($node->stmts === null) {
            return $result . ';';
        }

        return $result . $this->nl . '{'
            . $this->pStmts($node->stmts) . $this->nl
        . '}';
    }

    /**
     * <code>
     * // Before
     * function some() : void;
     *
     * // After
     * function some(): void;
     * </code>
     */
    protected function pStmt_Function(Stmt\Function_ $node): string
    {
        $result = $this->pAttrGroups($node->attrGroups)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pCommaSeparated($node->params) . ')';

        if ($node->returnType !== null) {
            $result .= ': ' . $this->p($node->returnType);
        }

        return $result . $this->nl . '{'
            . $this->pStmts($node->stmts) . $this->nl
        . '}';
    }

    /**
     * <code>
     * // Before
     * function () : void { ... }
     *
     * // After
     * function (): void { ... }
     * </code>
     */
    protected function pExpr_Closure(Expr\Closure $node): string
    {
        $result = $this->pAttrGroups($node->attrGroups, true);

        if ($node->static) {
            $result .= 'static ';
        }

        $result .= 'function ' . ($node->byRef ? '&' : '')
            . '(' . $this->pCommaSeparated($node->params) . ')';

        if ($node->uses !== []) {
            $result .= ' use(' . $this->pCommaSeparated($node->uses) . ')';
        }

        if ($node->returnType !== null) {
            $result .= ': ' . $this->p($node->returnType);
        }

        return $result . ' {'
            . $this->pStmts($node->stmts) . $this->nl
        . '}';
    }
}