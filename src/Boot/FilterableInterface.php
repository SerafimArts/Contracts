<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot;

interface FilterableInterface
{
    /**
     * @param class-string $namespace
     * @param class-string ...$namespaces
     * @return void
     */
    public function allow(string $namespace, string ...$namespaces): void;

    /**
     * @param class-string $class
     * @return bool
     */
    public function isAllowed(string $class): bool;
}