<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Metadata\Method\MethodModifier;
use Serafim\Contracts\Metadata\Method\MethodVisibility;

class MethodMetadata extends Metadata
{
    /**
     * @param non-empty-string $name
     * @param Location $location
     * @param list<AttributeMetadata<Verify>> $pre
     * @param list<AttributeMetadata<Ensure>> $post
     * @param list<MethodModifier> $modifiers
     * @param MethodVisibility $visibility
     */
    public function __construct(
        string $name,
        Location $location,
        public array $pre = [],
        public array $post = [],
        public array $modifiers = [],
        public MethodVisibility $visibility = MethodVisibility::PUBLIC,
    ) {
        parent::__construct($name, $location);
    }
}
