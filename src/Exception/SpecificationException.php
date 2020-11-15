<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * An error in contract annotation usage.
 */
class SpecificationException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    private const ERROR_INCOMPATIBLE_TYPE = '%s MUST contain PHP code string';

    /**
     * @param string $message
     * @param string $file
     * @param int $line
     * @return static
     */
    public static function create(string $message, string $file, int $line): self
    {
        $instance = new self($message);
        $instance->file = $file;
        $instance->line = $line;

        return $instance;
    }

    /**
     * @param string $type
     * @param string $file
     * @param int $line
     * @return static
     */
    public static function badType(string $type, string $file, int $line): self
    {
        $message = \sprintf(self::ERROR_INCOMPATIBLE_TYPE, $type);

        return self::create($message, $file, $line);
    }
}