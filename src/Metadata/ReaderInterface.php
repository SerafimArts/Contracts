<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

interface ReaderInterface
{
    /**
     * @param class-string $class
     * @return ClassLikeMetadata
     */
    public function read(string $class): ClassLikeMetadata;
}
