<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Attribute;

use JetBrains\PhpStorm\Language;
use Serafim\Contracts\Exception\InvariantException;

/**
 * Specifies class invariants that apply to the annotated type. The annotated
 * class must guarantee its invariants.
 *
 * When checking of contracts is enabled, class invariant are checked on entry
 * and exit of methods, and throw a {@see InvariantException} when they are
 * violated.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Invariant extends Contract
{
    /**
     * The invariant expression that must be met by the annotated type. The
     * expression must be valid PHP code and can reference all things visible
     * to the class, including private members.
     *
     * @psalm-taint-sink eval $expr
     * @param non-empty-string $expr
     * @param string|null $reason
     */
    public function __construct(
        #[Language('PHP')] string|callable $expr,
        ?string $reason = null,
    ) {
        parent::__construct($expr, $reason);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->expr;
    }
}
