<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Info;

use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Verify;

class MethodMetadata extends Metadata
{
    /**
     * @param non-empty-string $name
     * @param list<Verify> $pre
     * @param list<Ensure> $post
     * @param list<MethodModifier> $modifiers
     * @param MethodVisibility $visibility
     */
    public function __construct(
        string $name,
        public array $pre = [],
        public array $post = [],
        public array $modifiers = [],
        public MethodVisibility $visibility = MethodVisibility::PUBLIC,
    ) {
        parent::__construct($name);
    }
}
