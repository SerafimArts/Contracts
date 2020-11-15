<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts;

use Serafim\Contracts\Internal\Composer;
use Serafim\Contracts\Internal\Interceptor;

final class Runtime implements ApiInterface
{
    /**
     * @var string
     */
    private const DEFAULT_STORAGE = __DIR__ . '/../storage';

    /**
     * @var Interceptor|null
     */
    private static $interceptor;

    /**
     * @var bool
     */
    private static $enabled = false;

    /**
     * @internal This method can be called only from within the auto-loaded file using Composer.
     * @return bool
     */
    public static function init(): bool
    {
        if (self::$interceptor === null) {
            $loader = Composer::getClassLoader();

            self::$interceptor = new Interceptor($loader, self::DEFAULT_STORAGE);
            self::auto();

            return true;
        }

        return false;
    }

    /**
     * @param string $directory
     */
    public static function cache(string $directory): void
    {
        self::$interceptor->cache($directory);
    }

    /**
     * {@inheritDoc}
     */
    public static function listen(string $namespace, string ...$namespaces): void
    {
        self::$interceptor->allow(\array_merge([$namespace], $namespaces));
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public static function enable(): bool
    {
        assert(self::$interceptor !== null);

        if (self::$enabled === false) {
            self::$interceptor->enable();

            return self::$enabled = true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public static function disable(): bool
    {
        assert(self::$interceptor !== null);

        if (self::$enabled === true) {
            self::$interceptor->disable();

            return ! (self::$enabled = false);
        }

        return false;
    }
}