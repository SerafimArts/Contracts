<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal ReturnDecoratorVisitor is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
class ReturnDecoratorVisitor extends NodeVisitorAbstract
{
    /**
     * @var Variable
     */
    private $return;

    /**
     * @param Variable $return
     */
    public function __construct(Variable $return)
    {
        $this->return = $return;
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