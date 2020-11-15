<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal;

use Composer\Autoload\ClassLoader;
use function Composer\Autoload\includeFile;

/**
 * @internal Interceptor is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Interceptor
{
    /**
     * @var array<string|class-string>
     */
    private $namespaces = [];

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @param ClassLoader $loader
     * @param string|null $storage
     */
    public function __construct(ClassLoader $loader, string $storage = null)
    {
        $this->loader = $loader;
        $this->storage = new Storage($storage ?? \sys_get_temp_dir());
        $this->compiler = new Compiler();
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function cache(string $directory): self
    {
        $this->storage = new Storage($directory);

        return $this;
    }

    /**
     * @param array<string|class-string> $namespaces
     */
    public function allow(array $namespaces): void
    {
        foreach ($namespaces as $namespace) {
            $this->namespaces[] = \trim($namespace, '\\');
        }
    }

    /**
     * @return void
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function enable(): void
    {
        \spl_autoload_register([$this, 'loadClass'], true, true);
    }

    /**
     * @return void
     * @psalm-suppress UnusedFunctionCall
     */
    public function disable(): void
    {
        \spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * @param string $class
     * @return bool
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
     * @param class-string $class
     * @return bool
     * @throws \Throwable
     */
    public function loadClass(string $class): bool
    {
        if (! $this->isAllowed($class)) {
            return false;
        }

        try {
            return $this->findAndInclude($class);
        } catch (\Throwable $e) {
            $class = \get_class($e);

            $instance = new $class($e->getMessage(), $e->getCode(), $e->getPrevious());

            throw Exception::withLocation($instance, $e->getFile(), $e->getLine());
        }
    }

    /**
     * @param string $class
     * @return bool
     */
    private function findAndInclude(string $class): bool
    {
        $file = $this->loader->findFile($class);

        if ($file === false) {
            return false;
        }

        $compiled = $this->storage->cached($class, $file, function () use ($file) {
            return $this->compiler->compile($file);
        });

        includeFile($compiled);

        return true;
    }
}