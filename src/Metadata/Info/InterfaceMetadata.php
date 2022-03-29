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

final class InterfaceMetadata extends ClassLikeMetadata
{
    /**
     * @param class-string $name
     * @param array<non-empty-string, MethodMetadata> $methods
     * @param list<AttributeMetadata<Invariant>> $invariants
     * @param list<InterfaceMetadata> $interfaces
     */
    public function __construct(
        string $name,
        array $methods = [],
        array $invariants = [],
        public array $interfaces = [],
    ) {
        parent::__construct($name, $methods, $invariants);
    }
}