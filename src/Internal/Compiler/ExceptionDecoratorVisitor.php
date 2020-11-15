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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Internal\Exception;

/**
 * @internal ExceptionDecoratorVisitor is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
class ExceptionDecoratorVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $file;

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
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

    private function decorate(Expr $expr): Expr
    {
        return new StaticCall(new FullyQualified(Exception::class), 'withLocation', [
            new Node\Arg($expr),
            new Node\Arg(new Node\Scalar\String_($this->file)),
            new Node\Arg(new Node\Scalar\LNumber($expr->getLine()))
        ]);
    }
}