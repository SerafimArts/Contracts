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

/**
 * @internal Composer is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class Composer
{
    /**
     * @var string
     */
    private const GENERATED_LOADER_PREFIX = 'ComposerAutoloaderInit';

    /**
     * @var string
     */
    private const ERROR_INVALID_INIT_LOCATION = 'File "%s" MUST be loaded though Composer';

    /**
     * @return ClassLoader
     */
    public static function getClassLoader(): ClassLoader
    {
        $file  = null;
        $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) ?? [];

        while ($trace) {
            $current = \array_shift($trace);

            if ($file === null && ($current['file'] ?? __FILE__) !== __FILE__) {
                $file = $current['file'];
            }

            if (\str_starts_with($current['class'] ?? '', self::GENERATED_LOADER_PREFIX)) {
                return $current['class']::getLoader();
            }
        }

        throw new \LogicException(\sprintf(self::ERROR_INVALID_INIT_LOCATION, $file ?? __FILE__));
    }
}