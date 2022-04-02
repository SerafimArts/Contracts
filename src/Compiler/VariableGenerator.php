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
 * This class is used to generate variable names.
 */
final class VariableGenerator
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $variables = [];

    /**
     * @param string $prefix
     * @param non-empty-string $algo
     * @param positive-int $seedSize
     */
    public function __construct(
        private readonly string $prefix = '',
        private readonly string $algo = 'xxh32',
        private readonly int $seedSize = 32,
    ) {
    }

    /**
     * @param non-empty-string ...$parts
     * @return non-empty-string
     * @throws \Exception
     */
    public function generate(string ...$parts): string
    {
        $suffix = \hash($this->algo, \random_bytes($this->seedSize));

        return $this->prefix . \implode('−', [...$parts, $suffix]);
    }

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     * @throws \Exception
     */
    public function get(string $name): string
    {
        return $this->variables[$name] ??= $this->generate($name);
    }

    /**
     * @param null|callable(VariableGenerator):void $context
     * @return $this
     */
    public function context(callable $context = null): self
    {
        $instance = clone $this;

        if ($context !== null) {
            $context($instance);
        }

        return $instance;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->variables = [];
    }
}