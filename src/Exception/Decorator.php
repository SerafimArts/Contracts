<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Exception;

final class Decorator
{
    /**
     * @template TThrowable of \Throwable
     * @psalm-taint-sink file $file
     * @param TThrowable $e
     * @param non-empty-string $file
     * @param positive-int $line
     * @return TThrowable
     */
    public static function decorate(\Throwable $e, string $file, int $line): \Throwable
    {
        try {
            (new \ReflectionProperty($e, 'file'))->setValue($e, $file);
            (new \ReflectionProperty($e, 'line'))->setValue($e, $line);
        } catch (\ReflectionException) {
            return $e;
        }

        return $e;
    }
}