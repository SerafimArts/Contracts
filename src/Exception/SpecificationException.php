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
 * An error in contract annotation usage.
 *
 * @psalm-consistent-constructor
 */
class SpecificationException extends \InvalidArgumentException implements
    AssertionDefinitionExceptionInterface
{
    /**
     * @var non-empty-string
     */
    private const ERROR_EXPRESSION_TYPE = 'Argument #1 of %s MUST contain PHP code string';

    /**
     * @var non-empty-string
     */
    private const ERROR_REASON_TYPE = 'Argument #2 of %s MUST contain reason phrase string';

    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    final public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @psalm-taint-sink file $file
     * @param string $message
     * @param non-empty-string $file
     * @param positive-int $line
     * @return static
     */
    public static function create(string $message, string $file, int $line): static
    {
        /** @psalm-suppress RedundantCondition */
        assert($line > 0, new \InvalidArgumentException('Line must be greater than 0'));

        $instance = new static($message);
        $instance->file = $file;
        $instance->line = $line;

        return $instance;
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $type
     * @param non-empty-string $file
     * @param positive-int $line
     * @return static
     */
    public static function invalidExpressionType(string $type, string $file, int $line): static
    {
        $message = \sprintf(self::ERROR_EXPRESSION_TYPE, $type);

        return self::create($message, $file, $line);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $type
     * @param non-empty-string $file
     * @param positive-int $line
     * @return static
     */
    public static function invalidReasonType(string $type, string $file, int $line): static
    {
        $message = \sprintf(self::ERROR_REASON_TYPE, $type);

        return self::create($message, $file, $line);
    }
}
