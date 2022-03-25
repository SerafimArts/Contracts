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
 *
 * @psalm-consistent-constructor
 */
class SpecificationException extends \InvalidArgumentException implements ContractsExceptionInterface
{
    /**
     * @var non-empty-string
     */
    private const ERROR_INCOMPATIBLE_TYPE = '%s MUST contain PHP code string';

    /**
     * @psalm-taint-sink file $file
     * @param string $message
     * @param non-empty-string $file
     * @param positive-int $line
     * @return static
     */
    public static function create(string $message, string $file, int $line): static
    {
        assert($line > 0, new \InvalidArgumentException('Line must be greater than 0'));

        $instance = new static($message);
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
