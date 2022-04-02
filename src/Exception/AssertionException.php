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
 *
 * @psalm-consistent-constructor
 */
class AssertionException extends \AssertionError implements
    AssertionViolationExceptionInterface
{
    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $message
     * @param non-empty-string|null $file
     * @param positive-int|null $line
     */
    final public function __construct(string $message, string $file = null, int $line = null)
    {
        parent::__construct($this->createMessage($message));

        if ($file !== null && $line !== null) {
            [$this->file, $this->line] = [$file, $line];
        }
    }

    /**
     * @param string $message
     * @return string
     */
    protected function createMessage(string $message): string
    {
        $segments = \explode('\\', static::class);

        return \vsprintf('%s violation: %s', [
            \substr(\end($segments), 0, -9),
            $message,
        ]);
    }
}
