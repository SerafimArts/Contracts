<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

/**
 * @internal This is an internal library interface, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
interface CompilerInterface
{
    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $pathname
     * @return string
     */
    public function compile(string $pathname): string;
}
