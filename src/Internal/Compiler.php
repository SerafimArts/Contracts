<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Serafim\Contracts\Internal\Compiler\ContractsApplicatorVisitor;
use Serafim\Contracts\Internal\Compiler\ContractsParser;
use Serafim\Contracts\Internal\Compiler\ExceptionDecoratorVisitor;
use Serafim\Contracts\Internal\Compiler\MethodInjector;

/**
 * @internal Compiler is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Compiler
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    /**
     * @var ContractsParser
     */
    private $contracts;

    /**
     * @var MethodInjector
     */
    private $injector;

    /**
     * @param PrettyPrinterAbstract|null $printer
     * @param Parser|null $parser
     */
    public function __construct(PrettyPrinterAbstract $printer = null, Parser $parser = null)
    {
        $this->parser = $parser ?? $this->createParser();
        $this->printer = $printer ?? $this->createPrinter();

        $this->contracts = new ContractsParser($this->parser);
        $this->injector = new MethodInjector($this->contracts);
    }

    /**
     * @return Parser
     */
    private function createParser(): Parser
    {
        return (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
    }

    /**
     * @return PrettyPrinterAbstract
     */
    private function createPrinter(): PrettyPrinterAbstract
    {
        return new Standard();
    }

    /**
     * @param string $file
     * @return string
     */
    public function compile(string $file): string
    {
        $file = \realpath($file) ?? $file;

        $ast = $this->parser->parse(\file_get_contents($file));

        return $this->printer->prettyPrintFile(
            $this->process($file, $ast)
        );
    }

    /**
     * @param string $file
     * @param array $nodes
     * @return array
     */
    private function process(string $file, array $nodes): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new ExceptionDecoratorVisitor($file));
        $traverser->addVisitor(new ContractsApplicatorVisitor($file, $this->contracts, $this->injector));

        return $traverser->traverse($nodes);
    }
}