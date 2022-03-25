<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot;

use Serafim\Contracts\Boot\Loader\ComposerLoader;
use Serafim\Contracts\Boot\Loader\LoaderInterface;
use Serafim\Contracts\Compiler\Compiler;
use Serafim\Contracts\Runtime\Exception;

final class Interceptor
{
    /**
     * @var list<non-empty-string|class-string>
     */
    private array $namespaces = [];

    /**
     * @var Storage
     */
    private Storage $storage;

    /**
     * @var Compiler
     */
    private readonly Compiler $compiler;

    /**
     * @psalm-taint-sink file $storage
     * @param LoaderInterface $loader
     * @param non-empty-string|null $storage
     */
    public function __construct(private readonly LoaderInterface $loader, string $storage = null)
    {
        $this->compiler = new Compiler();
        $this->storage = new Storage($storage ?? \sys_get_temp_dir());
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
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     * @return $this
     */
    public function cache(string $directory): self
    {
        $this->storage = new Storage($directory);

        return $this;
    }

    /**
     * @param non-empty-string|class-string ...$namespaces
     * @return void
     */
    public function allow(string ...$namespaces): void
    {
        foreach ($namespaces as $namespace) {
            $this->namespaces[] = \trim($namespace, '\\');
        }
    }

    /**
     * @return void
     */
    public function enable(): void
    {
        \spl_autoload_register($this->loadClass(...), true, true);
    }

    /**
     * @return void
     */
    public function disable(): void
    {
        \spl_autoload_unregister($this->loadClass(...));
    }

    /**
     * @param class-string $class
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
            throw Exception::decorate($e);
        }
    }

    /**
     * @param class-string $class
     * @return bool
     */
    private function findAndInclude(string $class): bool
    {
        $file = $this->loader->getPathname($class);

        if ($file === false) {
            return false;
        }

        require $this->storage->cached($class, $file, function () use ($file) {
            return $this->compiler->compile($file);
        });

        return true;
    }
}
