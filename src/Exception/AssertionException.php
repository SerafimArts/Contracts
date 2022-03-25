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
        $segments = \explode('\\', static::class);

        parent::__construct(\vsprintf('%s assertion (%s) failed', [
            \substr(\end($segments), 0, -9),
            $message,
        ]));

        if ($file !== null && $line !== null) {
            [$this->file, $this->line] = [$file, $line];
        }
    }
}
