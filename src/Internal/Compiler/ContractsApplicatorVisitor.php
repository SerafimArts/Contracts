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
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Internal\Statement\InvariantStatement;

/**
 * @internal AnalyzerVisitor is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
class ContractsApplicatorVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string|null
     */
    private $class;

    /**
     * @var InvariantStatement[]
     */
    private $invariants = [];

    /**
     * @var ContractsParser
     */
    private $parser;

    /**
     * @var MethodInjector
     */
    private $injector;

    /**
     * @param string $file
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
     * @param Node $node
     * @return mixed|void
     * @throws \Exception
     */
    public function leaveNode(Node $node)
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