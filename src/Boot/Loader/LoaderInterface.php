<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot\Loader;

/**
 * @internal This is an internal library interface, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Boot
 */
interface LoaderInterface
{
    /**
     * @param class-string $class
     * @return non-empty-string|null
     */
    public function getPathname(string $class): ?string;
}
