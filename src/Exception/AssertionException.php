<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Exception;

/**
 * Base class for contract assertion errors. You should generally not catch this.
 */
class AssertionException extends \AssertionError
{
    /**
     * @param bool $result
     * @param string $expression
     * @param string $file
     * @param int $line
     */
    public static function throwIf(bool $result, string $expression, string $file, int $line): void
    {
        if ($result === false) {
            $instance = new static($expression);
            $instance->file = $file;
            $instance->line = $line;

            throw $instance;
        }
    }
}