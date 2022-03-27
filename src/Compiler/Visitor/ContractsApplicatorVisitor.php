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
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Compiler\ContractsParser;
use Serafim\Contracts\Compiler\MethodInjector;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\InvariantStatement;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Compiler
 */
final class ContractsApplicatorVisitor extends NodeVisitorAbstract
{
    /**
     * @var class-string|null
     */
    private ?string $class = null;

    /**
     * @var list<InvariantStatement>
     */
    private array $invariants = [];

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param ContractsParser $parser
     * @param MethodInjector $injector
     */
    public function __construct(
        private readonly string $file,
        private readonly ContractsParser $parser,
        private readonly MethodInjector $injector
    ) {
    }

    /**
     * @param Node $node
     * @return int|null
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Class_) {
            $this->class = $node->name->toString();
        }

        if ($node instanceof Node\Attribute) {
            return $this->onAttribute($node);
        }

        return parent::enterNode($node);
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node): void
    {
        // Clear all invariants
        if ($node instanceof Class_) {
            $this->invariants = [];
            $this->class = null;
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            if (($node->flags & Class_::MODIFIER_ABSTRACT) === Class_::MODIFIER_ABSTRACT) {
                return;
            }

            $this->injector->inject($this->file, $node, $this->invariants);
        }
    }

    /**
     * @param Node\Attribute $attribute
     * @return int
     */
    private function onAttribute(Node\Attribute $attribute): int
    {
        if ($this->class === null) {
            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        if ($attribute->name->toString() === Invariant::class) {
            foreach ($this->parser->invariant($this->file, $attribute) as $stmt) {
                $this->invariants[] = $stmt;
            }
        }

        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
    }
}
