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

abstract class Contract implements \Stringable
{
    /**
     * @psalm-taint-sink eval $expr
     * @param non-empty-string $expr
     * @param string|null $reason
     */
    public function __construct(
        #[Language('PHP')] public readonly string $expr,
        public readonly ?string $reason = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->expr;
    }
}