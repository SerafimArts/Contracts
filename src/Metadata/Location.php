<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

final class Location
{
    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        public string $file,
        public int $line = 1,
    ) {
        $this->file = \str_replace('\\', '/', $this->file);
    }

    /**
     * @return static
     */
    public static function empty(): self
    {
        return new Location(__FILE__, __LINE__);
    }
}
