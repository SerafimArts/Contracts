<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Runtime;

final class Exception
{
    /**
     * @param \Throwable $e
     * @return \Throwable
     * @throws \ReflectionException
     */
    public static function decorate(\Throwable $e): \Throwable
    {
        $instance = new ($e::class)($e->getMessage(), (int)$e->getCode(), $e->getPrevious());

        return Exception::withLocation($instance, $e->getFile(), $e->getLine());
    }

    /**
     * @psalm-taint-sink file $file
     * @param \Throwable $e
     * @param non-empty-string $file
     * @param positive-int $line
     * @return \Throwable
     * @throws \ReflectionException
     */
    public static function withLocation(\Throwable $e, string $file, int $line): \Throwable
    {
        (new \ReflectionProperty($e, 'file'))->setValue($e, $file);
        (new \ReflectionProperty($e, 'line'))->setValue($e, $line);

        return $e;
    }
}
