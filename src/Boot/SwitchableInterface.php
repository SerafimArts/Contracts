<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot;

interface SwitchableInterface
{
    /**
     * @return void
     */
    public function enable(): void;

    /**
     * @return void
     */
    public function disable(): void;
}