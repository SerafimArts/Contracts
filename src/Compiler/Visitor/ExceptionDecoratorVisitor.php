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
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Exception\Decorator;

/**
 * Catches all {@see \Throwable} thrown in this class and replaces all
 * references to the location of this exception with the original location.
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler
 */
final class ExceptionDecoratorVisitor extends NodeVisitorAbstract
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
     * @return int|null
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Throw_) {
            $node->expr = new StaticCall(new FullyQualified(Decorator::class), 'decorate', [
                new Node\Arg($node->expr),
                new Node\Arg(new Node\Scalar\String_($this->file)),
                new Node\Arg(new Node\Scalar\LNumber($node->expr->getLine()))
            ]);

            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        return null;
    }
}
