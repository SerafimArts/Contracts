<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Boot\Loader;

use Composer\Autoload\ClassLoader;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts\Boot
 */
abstract class ComposerLoader implements LoaderInterface
{
    /**
     * @param ClassLoader $loader
     */
    public function __construct(
        private readonly ClassLoader $loader,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getPathname(string $class): ?string
    {
        return $this->loader->findFile($class) ?: null;
    }
}
