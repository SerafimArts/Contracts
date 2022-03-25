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
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ReturnDecoratorVisitor extends NodeVisitorAbstract
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
     * @return int|null
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Return_) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return parent::enterNode($node);
    }

    /**
     * @param Node $node
     * @return null
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Return_) {
            $node->expr = new Node\Expr\Assign($this->return, $node->expr);
        }

        return parent::leaveNode($node);
    }
}
