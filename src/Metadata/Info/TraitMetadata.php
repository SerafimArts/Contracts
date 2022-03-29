<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Info;

use Serafim\Contracts\Attribute\Invariant;

/**
 * @template TObject of object
 */
final class TraitMetadata extends ClassLikeMetadata
{
    /**
     * @param class-string<TObject> $name
     * @param array<non-empty-string, MethodMetadata> $methods
     * @param list<AttributeMetadata<Invariant>> $invariants
     * @param list<TraitMetadata> $traits
     */
    public function __construct(
        string $name,
        array $methods = [],
        array $invariants = [],
        public array $traits = [],
    ) {
        parent::__construct($name, $methods, $invariants);
    }
}