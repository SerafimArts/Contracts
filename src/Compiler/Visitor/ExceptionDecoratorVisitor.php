<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Runtime\Exception;

class ExceptionDecoratorVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     */
    public function __construct(
        private readonly string $file,
    ) {
    }

    /**
     * @param Node $node
     * @return int|void
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Throw_) {
            $node->expr = $this->decorate($node->expr);

            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }
    }

    /**
     * @param Expr $expr
     * @return Expr
     */
    private function decorate(Expr $expr): Expr
    {
        return new StaticCall(new FullyQualified(Exception::class), 'withLocation', [
            new Node\Arg($expr),
            new Node\Arg(new Node\Scalar\String_($this->file)),
            new Node\Arg(new Node\Scalar\LNumber($expr->getLine()))
        ]);
    }
}
