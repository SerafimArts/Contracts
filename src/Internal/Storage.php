<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal;

use JetBrains\PhpStorm\Pure;

/**
 * @internal Storage is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Storage
{
    /**
     * @var string
     */
    private const ERROR_CREATE_DIRECTORY = 'Storage directory "%s" was not created';

    /**
     * @var string
     */
    private $directory;

    /**
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = \rtrim($directory, '/\\');
    }

    /**
     * @param string $class
     * @return string
     */
    #[Pure]
    private function key(string $class): string
    {
        return $this->directory . '/' . \str_replace('\\', '/', $class) . '.php';
    }

    /**
     * @param string $class
     * @param string $pathname
     * @param \Closure $then
     * @return string
     */
    public function cached(string $class, string $pathname, \Closure $then): string
    {
        $target = $this->key($class);

        // Concurrent concurrent creation
        if (! @\mkdir($concurrent = \dirname($target), 0777, true) && ! \is_dir($concurrent)) {
            throw new \RuntimeException(\sprintf(self::ERROR_CREATE_DIRECTORY, $concurrent));
        }

        if (! \is_file($target) || \filemtime($pathname) > \filemtime($target)) {
            \file_put_contents($target, $result = $then(), \LOCK_EX);
        }

        return $target;
    }
}