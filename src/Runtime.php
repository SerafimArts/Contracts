<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts;

use Serafim\Contracts\Boot\Cache\RebuildableInterface;

final class Runtime
{
    /**
     * @var non-empty-string
     */
    private const DEFAULT_STORAGE = __DIR__ . '/../storage';

    /**
     * @var Processor|null
     */
    private static ?Processor $interceptor = null;

    /**
     * @var bool
     */
    private static bool $enabled = false;

    /**
     * @return Processor
     */
    public static function boot(): Processor
    {
        if (self::$interceptor === null) {
            self::$interceptor = Processor::fromComposer(self::DEFAULT_STORAGE);
            self::auto();
        }

        return self::$interceptor;
    }

    /**
     * Specifying the directory where decorated files are saved.
     *
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     */
    public static function cache(string $directory): void
    {
        $interceptor = self::boot();
        $interceptor->cache->in($directory);
    }

    /**
     * Adds namespaces or classes to the list of files to be decorated.
     *
     * @param non-empty-string|class-string $namespace
     * @param (non-empty-string|class-string) ...$namespaces
     * @return void
     */
    public static function listen(string $namespace, string ...$namespaces): void
    {
        $interceptor = self::boot();
        $interceptor->allow($namespace, ...$namespaces);
    }

    /**
     * Enable DbC runtime assertions ({@see enable()}) in case of PHP
     * `assert.active` are enabled in `php.ini` configuration file or
     * disable ({@see disable()}) otherwise.
     *
     * @return bool
     */
    public static function auto(): bool
    {
        $result = false;

        assert(self::enable() || $result = true);

        if ($result === false) {
            self::disable();
        }

        return $result;
    }

    /**
     * Forces all DbC assertion checks on.
     *
     * This is the default value for DEBUG environment.
     *
     * @return bool
     */
    public static function enable(): bool
    {
        if (self::$enabled === false) {
            $interceptor = self::boot();
            $interceptor->enable();

            return self::$enabled = true;
        }

        return false;
    }

    /**
     * Disables all DbC assertions.
     *
     * This is the default value for PRODUCTION environment.
     *
     * @return bool
     */
    public static function disable(): bool
    {
        if (self::$enabled === true) {
            $interceptor = self::boot();
            $interceptor->disable();

            return ! (self::$enabled = false);
        }

        return false;
    }

    /**
     * @param bool $enabled
     * @return bool
     */
    public static function rebuild(bool $enabled = true): bool
    {
        $interceptor = self::boot();

        if ($interceptor->cache instanceof RebuildableInterface) {
            return $interceptor->cache->rebuild($enabled);
        }

        throw new \BadMethodCallException('Cache driver does not support rebuild behaviour');
    }
}
