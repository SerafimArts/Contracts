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
final class ComposerReflectionLoader extends ComposerLoader
{
    /**
     * @var non-empty-string
     */
    private const ERROR_NOT_FOUND = 'Could not found suitable autoloader: File %s not found';

    public function __construct()
    {
        $autoload = $this->lookup($this->getVendorDirectory());

        parent::__construct(require $autoload);
    }

    /**
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory
     * @return non-empty-string
     */
    private function lookup(string $directory): string
    {
        $classLoader = $directory . '/autoload.php';

        // Lookup default autoload.php file
        if (!\is_file($classLoader)) {
            throw new \LogicException(\sprintf(self::ERROR_NOT_FOUND, $classLoader));
        }

        return $classLoader;
    }

    /**
     * @return non-empty-string
     */
    private function getVendorDirectory(): string
    {
        $reflection = new \ReflectionClass(ClassLoader::class);

        return \dirname($reflection->getFileName(), 2);
    }

}
