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
 * Replaces all occurrences of {@see __DIR__}, {@see __FILE__}
 * and {@see __LINE__} directives with the original location.
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler
 */
final class ConstReplaceVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     * @param positive-int $line
     */
    public function __construct(
        private readonly string $pathname,
        private readonly int $line = 1,
    ) {
    }

    /**
     * @param Node $node
     * @return Node|null
     */
    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Scalar\MagicConst\Dir) {
            return new Node\Scalar\String_(\dirname($this->pathname));
        }

        if ($node instanceof Node\Scalar\MagicConst\File) {
            return new Node\Scalar\String_($this->pathname);
        }

        if ($node instanceof Node\Scalar\MagicConst\Line) {
            return new Node\Scalar\LNumber($this->line + $node->getLine() - 1);
        }

        return null;
    }
}
