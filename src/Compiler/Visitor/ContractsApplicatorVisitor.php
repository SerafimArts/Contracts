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
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Compiler\ContractsParser;
use Serafim\Contracts\Compiler\MethodInjector;
use Serafim\Contracts\Compiler\Statement\InvariantStatement;

class ContractsApplicatorVisitor extends NodeVisitorAbstract
{
    /**
     * @var non-empty-string
     */
    private string $file;

    /**
     * @var non-empty-string|null
     */
    private ?string $namespace = null;

    /**
     * @var class-string|null
     */
    private ?string $class = null;

    /**
     * @var list<InvariantStatement>
     */
    private array $invariants = [];

    /**
     * @var ContractsParser
     */
    private ContractsParser $parser;

    /**
     * @var MethodInjector
     */
    private MethodInjector $injector;

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param ContractsParser $parser
     * @param MethodInjector $injector
     */
    public function __construct(string $file, ContractsParser $parser, MethodInjector $injector)
    {
        $this->file = $file;
        $this->parser = $parser;
        $this->injector = $injector;
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = null;
        }

        // Clear all invariants
        if ($node instanceof Node\Stmt\Class_) {
            $this->invariants = [];
            $this->class = null;
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->injector->inject($this->file, $node, $this->invariants);
        }
    }

    /**
     * @param Node $node
     * @return int|null
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name->toString();
        }

        if ($node instanceof Node\Stmt\Class_) {
            $this->class = $node->name->toString();
        }

        if ($node instanceof Node\Attribute) {
            return $this->onAttribute($node);
        }

        return parent::enterNode($node);
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
