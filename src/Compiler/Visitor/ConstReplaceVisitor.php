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
use PhpParser\NodeVisitorAbstract;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler
 */
final class ConstReplaceVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        private readonly string $file,
        private readonly int $line,
    ) {
    }

    /**
     * @param Node $node
     * @return mixed|void
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Scalar\MagicConst\Dir) {
            return new Node\Scalar\String_(\dirname($this->file));
        }

        if ($node instanceof Node\Scalar\MagicConst\File) {
            return new Node\Scalar\String_($this->file);
        }

        if ($node instanceof Node\Scalar\MagicConst\Line) {
            return new Node\Scalar\LNumber($this->line + $node->getLine() - 1);
        }

        return parent::leaveNode($node);
    }
}
