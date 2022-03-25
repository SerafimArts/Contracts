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
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler
 */
final class ReturnDecoratorVisitor extends NodeVisitorAbstract
{
    /**
     * @param Variable $return
     */
    public function __construct(
        private readonly Variable $return
    ) {
    }

    /**
     * @param Node $node
     * @return void|list<Node>
     */
    public function leaveNode(Node $node)
    {
       if (!$node instanceof Node\Stmt\Return_) {
           return;
       }

       if ($node->expr !== null) {
           $node->expr = new Node\Expr\Assign($this->return, $node->expr);

           return;
       }

        return [
            new Expression(
                new Node\Expr\Assign($this->return, new Node\Expr\ConstFetch(
                    new Name('null')
                ))
            ),
            new Node\Stmt\Return_(),
        ];
    }
}
