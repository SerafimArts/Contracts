<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot;

interface ProcessorInterface extends
    SwitchableInterface,
    FilterableInterface
{
    /**
     * Finds the path to the file where the class is defined, then process it
     * and include.
     *
     * @param class-string $class
     * @return bool
     * @throws \Throwable
     */
    public function loadClass(string $class): bool;
}