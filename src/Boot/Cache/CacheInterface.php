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
 * @internal This is an internal library interface, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
interface CacheInterface
{
    /**
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     * @return void
     */
    public function in(string $directory): void;

    /**
     * @psalm-taint-sink file $pathname
     * @param class-string $class
     * @param non-empty-string $pathname
     * @param callable():string $then
     * @return string
     */
    public function get(string $class, string $pathname, callable $then): string;
}