<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot;

final class Storage
{
    /**
     * @var non-empty-string
     */
    private const ERROR_CREATE_DIRECTORY = 'Storage directory "%s" was not created';

    /**
     * @var non-empty-string
     */
    private readonly string $directory;

    /**
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     */
    public function __construct(
        string $directory,
        private bool $debug = false
    ) {
        $this->directory = \rtrim($directory, '/\\');
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
     * @psalm-taint-sink file $pathname
     * @param class-string $class
     * @param non-empty-string $pathname
     * @param \Closure():string $then
     * @return string
     */
    public function cached(string $class, string $pathname, \Closure $then): string
    {
        $target = $this->key($class);

        // Concurrent concurrent creation
        if (! @\mkdir($concurrent = \dirname($target), 0777, true) && ! \is_dir($concurrent)) {
            throw new \RuntimeException(\sprintf(self::ERROR_CREATE_DIRECTORY, $concurrent));
        }

        if (!\is_file($target) || \filemtime($pathname) > \filemtime($target) || $this->debug) {
            \file_put_contents($target, $then(), \LOCK_EX);
        }

        return $target;
    }
}
