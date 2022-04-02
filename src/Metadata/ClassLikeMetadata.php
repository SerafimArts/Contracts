<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

use Serafim\Contracts\Attribute\Invariant;

abstract class ClassLikeMetadata extends Metadata
{
    /**
     * @param class-string $name
     * @param Location $location
     * @param array<non-empty-string, MethodMetadata> $methods
     * @param list<AttributeMetadata<Invariant>> $invariants
     */
    public function __construct(
        string $name,
        Location $location,
        public array $methods = [],
        public array $invariants = [],
    ) {
        parent::__construct($name, $location);
    }
}
