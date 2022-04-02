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
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as ParserInterface;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinterAbstract;
use Serafim\Contracts\Compiler\Pipeline\PsrPrinter;
use Serafim\Contracts\Compiler\Visitor\ConstReplaceVisitor;
use Serafim\Contracts\Compiler\Visitor\ExceptionDecoratorVisitor;
use Serafim\Contracts\Exception\Decorator;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Pipeline
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
     * @param PrettyPrinterAbstract|null $printer
     * @param ParserInterface|null $parser
     */
    public function __construct(
        PrettyPrinterAbstract $printer = null,
        ParserInterface $parser = null,
    ) {
        $this->parser = $parser ?? $this->createParser();
        $this->printer = $printer ?? $this->createPrinter();
    }

    /**
     * @return ParserInterface
     */
    private function createParser(): ParserInterface
    {
        return (new ParserFactory())
            ->create(ParserFactory::ONLY_PHP7, new Emulative([
                'usedAttributes' => ['startLine', 'startFilePos', 'endFilePos'],
            ]));
    }

    /**
     * @return PrettyPrinterAbstract
     */
    private function createPrinter(): PrettyPrinterAbstract
    {
        return new PsrPrinter();
    }

    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     * @return string
     * @throws \Throwable
     */
    public function process(string $pathname): string
    {
        $pathname = \realpath($pathname) ?? $pathname;

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(options: ['preserveOriginalNames' => true]));
        $traverser->addVisitor(new ConstReplaceVisitor($pathname));
        $traverser->addVisitor(new ExceptionDecoratorVisitor($pathname));

        return $this->printer->prettyPrintFile(
            $traverser->traverse(
                $this->parse($pathname)
            )
        );
    }

    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     * @return array<Stmt>|null
     * @throws \Throwable
     */
    private function parse(string $pathname): ?array
    {
        try {
            return $this->parser->parse(\file_get_contents($pathname));
        } catch (Error $e) {
            throw $this->parsingError($e, $pathname);
        }

    }

    /**
     * @psalm-taint-sink file $file
     * @param Error $e
     * @param non-empty-string $file
     * @return \Throwable
     */
    private function parsingError(Error $e, string $file): \Throwable
    {
        $error = new \ParseError($e->getMessage(), (int)$e->getCode(), $e);

        return Decorator::decorate($error, $file, $e->getStartLine());
    }
}
