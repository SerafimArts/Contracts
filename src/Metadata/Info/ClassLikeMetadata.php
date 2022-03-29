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

abstract class ClassLikeMetadata extends Metadata
{
    /**
     * @param class-string $name
     * @param array<non-empty-string, MethodMetadata> $methods
     * @param list<Invariant> $invariants
     */
    public function __construct(
        string $name,
        public array $methods = [],
        public array $invariants = [],
    ) {
        parent::__construct($name);
    }
}