<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Internal;

/**
 * @internal Exception is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
class Exception
{
    /**
     * @param \Throwable $e
     * @param string $file
     * @param int $line
     * @return \Throwable
     * @throws \ReflectionException
     */
    public static function withLocation(\Throwable $e, string $file, int $line): \Throwable
    {
        $fileProperty = new \ReflectionProperty($e, 'file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($e, $file);

        $lineProperty = new \ReflectionProperty($e, 'line');
        $lineProperty->setAccessible(true);
        $lineProperty->setValue($e, $line);

        return $e;
    }
}