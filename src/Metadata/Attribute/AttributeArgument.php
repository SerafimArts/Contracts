<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Attribute;

use Serafim\Contracts\Metadata\Location;

/**
 * @template T of mixed
 */
class AttributeArgument
{
    /**
     * @param T $value
     * @param Location $location
     */
    public function __construct(
        public mixed $value,
        public Location $location,
    ) {
    }

    /**
     * @return T
     */
    public function eval(): mixed
    {
        return $this->value;
    }
}
