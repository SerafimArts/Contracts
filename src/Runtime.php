<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts;

use PhpParser\NodeVisitor;
use Serafim\Contracts\Boot\Cache\RebuildableInterface;
use Serafim\Contracts\Boot\Loader\ComposerReflectionLoader;
use Serafim\Contracts\Boot\Loader\ComposerTracingLoader;
use Serafim\Contracts\Boot\ProcessorInterface;
use Serafim\Contracts\Boot\Processor;

/**
 * This facade class is responsible for interacting with DbC
 * (Design By Contract) library subsystems.
 *
 * @api This is a part of the primary public API of a package.
 */
final class Runtime
{
    /**
     * @var non-empty-string
     */
    private const ERROR_NOT_REBUILDABLE = 'Cache driver does not support rebuild behaviour';

    /**
     * @var non-empty-string
     */
    private const DEFAULT_STORAGE = __DIR__ . '/../storage';

    /**
     * @var Processor|null
     */
    private static ?Processor $interceptor = null;

    /**
     * This is the main bootstrap method that returns the main handler
     * instance ({@see ProcessorInterface}) to which this facade delegates all
     * calls.
     *
     * @return Processor
     */
    public static function boot(): Processor
    {
        if (self::$interceptor === null) {
            try {
                // The main loader that initializes the subsystem based on the
                // backtrace with the search for the autoloading file.
                //
                // It is guaranteed to work if this method is called using the
                // Composer: For example, when loading the "bootstrap.php" file
                // automatically.
                $loader = new ComposerTracingLoader();
            } catch (\Throwable) {
                // In case of an error loading through the backtrace, we will
                // try to load the class loader through PHP Reflection, based
                // on the class known to us, the Composer\Autoload\ClassLoader
                // class.
                $loader = new ComposerReflectionLoader();
            }

            self::$interceptor = new Processor($loader, self::DEFAULT_STORAGE);
            self::auto();
        }

        return self::$interceptor;
    }

    /**
     * Specifying the directory where decorated files are saved.
     *
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     * @return class-string<self>
     */
    public static function cache(string $directory): string
    {
        $interceptor = self::boot();
        $interceptor->cache->in($directory);

        return self::class;
    }

    /**
     * Adds namespaces or classes to the list of files to be decorated.
     *
     * @param non-empty-string|class-string $namespace
     * @param (non-empty-string|class-string) ...$namespaces
     * @return class-string<self>
     */
    public static function listen(string $namespace, string ...$namespaces): string
    {
        $interceptor = self::boot();
        $interceptor->allow($namespace, ...$namespaces);

        return self::class;
    }

    /**
     * Enable DbC runtime assertions ({@see enable()}) in case of PHP
     * `assert.active` are enabled in `php.ini` configuration file or
     * disable ({@see disable()}) otherwise.
     *
     * @return class-string<self>
     */
    public static function auto(): string
    {
        $enabled = false;
        assert($enabled = true);

        if ($enabled) {
            self::enable();
        } else {
            self::disable();
        }

        return self::class;
    }

    /**
     * Forces all DbC assertion checks on.
     *
     * This is the default value for DEBUG environment.
     *
     * @return class-string<self>
     */
    public static function enable(): string
    {
        $interceptor = self::boot();
        $interceptor->enable();

        return self::class;
    }

    /**
     * Disables all DbC assertions.
     *
     * This is the default value for PRODUCTION environment.
     *
     * @return class-string<self>
     */
    public static function disable(): string
    {
        $interceptor = self::boot();
        $interceptor->disable();

        return self::class;
    }

    /**
     * This method is needed mainly for debugging.
     *
     * Enables the mode of constant regeneration of the generated sources to
     * avoid "sticking" the opcache and testing the AST visitors
     * ({@see NodeVisitor}) introduced into the compilation pipeline.
     *
     * @internal Enables or disables debug mode. For internal use.
     *
     * @param bool $enabled
     * @return class-string<self>
     */
    public static function rebuild(bool $enabled = true): string
    {
        $interceptor = self::boot();

        if (! $interceptor->cache instanceof RebuildableInterface) {
            throw new \BadMethodCallException(self::ERROR_NOT_REBUILDABLE);
        }

        $interceptor->cache->rebuild($enabled);

        return self::class;
    }
}
