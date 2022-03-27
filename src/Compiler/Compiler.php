<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as ParserInterface;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Serafim\Contracts\Compiler\Visitor\ConstReplaceVisitor;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor;
use Serafim\Contracts\Compiler\Visitor\ExceptionDecoratorVisitor;
use Serafim\Contracts\Exception\Decorator;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Compiler implements CompilerInterface
{
    /**
     * @var ParserInterface
     */
    private ParserInterface $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private PrettyPrinterAbstract $printer;

    /**
     * @var ContractsParser
     */
    private ContractsParser $contracts;

    /**
     * @var MethodInjector
     */
    private MethodInjector $injector;

    /**
     * @param PrettyPrinterAbstract|null $printer
     * @param ParserInterface|null $parser
     */
    public function __construct(
        PrettyPrinterAbstract $printer = null,
        ParserInterface $parser = null
    ) {
        $this->parser = $parser ?? $this->createParser();
        $this->printer = $printer ?? $this->createPrinter();

        $this->contracts = new ContractsParser($this->parser);
        $this->injector = new MethodInjector($this->contracts);
    }

    /**
     * @return ParserInterface
     */
    private function createParser(): ParserInterface
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
     * {@inheritDoc}
     */
    public function compile(string $pathname): string
    {
        $pathname = \realpath($pathname) ?? $pathname;

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(options: ['preserveOriginalNames' => true]));
        $traverser->addVisitor(new ConstReplaceVisitor($pathname, 1));
        $traverser->addVisitor(new ExceptionDecoratorVisitor($pathname));
        $traverser->addVisitor(new ContractsApplicatorVisitor($pathname, $this->contracts, $this->injector));

        try {
            $ast = $this->parser->parse(\file_get_contents($pathname));
        } catch (Error $e) {
            $error = new \ParseError($e->getMessage(), (int)$e->getCode(), $e);
            throw Decorator::decorate($error, $pathname, $e->getStartLine());
        }

        return $this->printer->prettyPrintFile(
            $traverser->traverse($ast)
        );
    }
}
