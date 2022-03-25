<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Exception;

use JetBrains\PhpStorm\Language;
use Serafim\Contracts\Runtime\Exception;

/**
 * Base class for contract assertion errors. You should generally not catch this.
 *
 * @psalm-consistent-constructor
 */
class AssertionException extends \AssertionError implements ContractsExceptionInterface
{
    /**
     * @psalm-taint-sink eval $expression
     * @psalm-taint-sink file $file
     *
     * @param bool $result
     * @param non-empty-string $expression
     * @param non-empty-string $file
     * @param positive-int $line
     * @return void
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function throwIf(bool $result, #[Language('PHP')] string $expression, string $file, int $line): void
    {
        if ($result === false) {
            $instance = new static($expression);
            $instance->file = $file;
            $instance->line = $line;

            throw Exception::withLocation($instance, $file, $line);
        }
    }
}
