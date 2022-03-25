<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts;

use Serafim\Contracts\Boot\Cache\Cache;
use Serafim\Contracts\Boot\Cache\CacheInterface;
use Serafim\Contracts\Boot\Loader\ComposerLoader;
use Serafim\Contracts\Boot\Loader\LoaderInterface;
use Serafim\Contracts\Boot\ProcessorInterface;
use Serafim\Contracts\Compiler\Compiler;
use Serafim\Contracts\Exception\Decorator;

use function Composer\Autoload\includeFile;

final class Processor implements ProcessorInterface
{
    /**
     * @var list<non-empty-string|class-string>
     */
    private array $namespaces = [];

    /**
     * @var CacheInterface
     */
    public readonly CacheInterface $cache;

    /**
     * @var Compiler
     */
    private readonly Compiler $compiler;

    /**
     * @var bool
     */
    private bool $enabled = false;

    /**
     * @psalm-taint-sink file $storage
     * @param LoaderInterface $loader
     * @param non-empty-string|null $storage
     */
    public function __construct(
        private readonly LoaderInterface $loader,
        string $storage = null
    ) {
        $this->compiler = new Compiler();

        /** @psalm-suppress ArgumentTypeCoercion: Non-empty string provided */
        $this->cache = new Cache($storage ?? \sys_get_temp_dir());
    }

    /**
     * @psalm-taint-sink file $storage
     * @param non-empty-string|null $storage
     * @return static
     */
    public static function fromComposer(string $storage = null): self
    {
        return new self(ComposerLoader::create(), $storage);
    }

    /**
     * {@inheritDoc}
     */
    public function allow(string $namespace, string ...$namespaces): void
    {
        foreach ([$namespace, ...$namespaces] as $namespace) {
            $this->namespaces[] = \trim($namespace, '\\');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function enable(): void
    {
        $this->enabled = true;
        \spl_autoload_register([$this, 'loadClass'], true, true);
    }

    /**
     * {@inheritDoc}
     */
    public function disable(): void
    {
        $this->enabled = false;
        \spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(string $class): bool
    {
        foreach ($this->namespaces as $namespace) {
            if (\str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function loadClass(string $class): bool
    {
        if ($this->enabled === false || $this->isAllowed($class) === false) {
            return false;
        }

        try {
            return $this->processAndInclude($class);
        } catch (\Throwable $e) {
            $exception = new ($e::class)($e->getMessage(), (int)$e->getCode(), $e);

            throw Decorator::decorate($exception, $e->getFile(), $e->getLine());
        }
    }

    /**
     * @param class-string $class
     * @return bool
     */
    private function processAndInclude(string $class): bool
    {
        $file = $this->loader->getPathname($class);

        if ($file === false) {
            return false;
        }

        $pathname = $this->cache->get($class, $file, function () use ($file) {
            return $this->compiler->compile($file);
        });

        if (\function_exists('includeFile')) {
            includeFile($pathname);
        } else {
            require $pathname;
        }

        return true;
    }
}
