<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts;

interface ApiInterface
{
    /**
     * @return bool
     */
    public static function auto(): bool;

    /**
     * @return bool
     */
    public static function enable(): bool;

    /**
     * @return bool
     */
    public static function disable(): bool;

    /**
     * @param string $namespace
     * @param string|class-string ...$namespaces
     * @return void
     */
    public static function listen(string $namespace, string ...$namespaces): void;

    /**
     * @param string $directory
     */
    public static function cache(string $directory): void;
}