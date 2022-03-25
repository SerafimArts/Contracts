<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Statement;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Serafim\Contracts\Exception\InvariantException;

final class InvariantStatement extends Statement
{
    /**
     * @param Expr $expression
     * @return Expression
     */
    protected function wrap(Expr $expression): Expression
    {
        $exception = new FullyQualified(InvariantException::class);

        return new Expression(new StaticCall($exception, 'throwIf', [
            new Arg($expression),
            new Arg(new String_($this->expression)),
            new Arg(new String_($this->file)),
            new Arg(new LNumber($this->line)),
        ]));
    }
}
