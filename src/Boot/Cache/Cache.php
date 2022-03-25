<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot\Cache;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Cache implements CacheInterface, RebuildableInterface
{
    /**
     * @var non-empty-string
     */
    private const ERROR_CREATE_DIRECTORY = 'Storage directory "%s" was not created';

    /**
     * @var non-empty-string
     */
    private string $directory;

    /**
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     * @param bool $rebuild
     */
    public function __construct(
        string $directory,
        private bool $rebuild = false
    ) {
        $this->in($directory);
    }

    /**
     * {@inheritDoc}
     */
    public function in(string $directory): void
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->directory = \rtrim($directory, '/\\');

        assert($this->directory !== '', 'Cache directory must not be empty');
    }

    /**
     * {@inheritDoc}
     */
    public function rebuild(bool $enabled = null): bool
    {
        if ($enabled !== null) {
            $this->rebuild = $enabled;
        }

        return $this->rebuild;
    }

    /**
     * @param class-string $class
     * @return non-empty-string
     */
    private function key(string $class): string
    {
        return $this->directory . '/' . \str_replace('\\', '/', $class) . '.php';
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $class, string $pathname, callable $then): string
    {
        $target = $this->key($class);

        // Concurrent directory creation
        if (!@\mkdir($concurrent = \dirname($target), recursive: true) && !\is_dir($concurrent)) {
            throw new \RuntimeException(\sprintf(self::ERROR_CREATE_DIRECTORY, $concurrent));
        }

        if (!\is_file($target) || \filemtime($pathname) > \filemtime($target) || $this->rebuild) {
            \file_put_contents($target, $then(), \LOCK_EX);
        }

        return $target;
    }
}
