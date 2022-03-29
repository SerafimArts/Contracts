<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Info;

use Serafim\Contracts\Attribute\Contract;

/**
 * @template T of Contract
 * @see Contract
 */
final class AttributeMetadata extends Metadata
{
    /**
     * @param class-string<T> $name
     * @param array $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments = [],
    ) {
    }
}