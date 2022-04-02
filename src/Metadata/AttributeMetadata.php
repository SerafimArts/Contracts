<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

use Serafim\Contracts\Attribute\Contract;
use Serafim\Contracts\Exception\SpecificationException;
use Serafim\Contracts\Metadata\Attribute\AttributeArgument;

/**
 * @template T of Contract
 * @see Contract
 */
final class AttributeMetadata extends Metadata
{
    /**
     * @param class-string<T> $name
     * @param array<AttributeArgument> $arguments
     * @param Location $location
     */
    public function __construct(
        string $name,
        Location $location,
        public array $arguments = [],
    ) {
        parent::__construct($name, $location);
    }

    /**
     * @return T
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress InvalidStringClass
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @throws \Throwable
     */
    public function newInstance(): object
    {
        $arguments = [];

        foreach ($this->arguments as $i => $argument) {
            try {
                $arguments[$i] = $argument->eval();
            } catch (SpecificationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw SpecificationException::create($e, $argument->location->file, $argument->location->line);
            }
        }

        try {
            return new ($this->name)(...$arguments);
        } catch (SpecificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw SpecificationException::create($e, $this->location->file, $this->location->line);
        }
    }
}
