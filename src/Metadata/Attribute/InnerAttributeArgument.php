<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Attribute;

use Serafim\Contracts\Exception\SpecificationException;
use Serafim\Contracts\Metadata\AttributeMetadata;
use Serafim\Contracts\Metadata\Location;

/**
 * @template T of AttributeMetadata
 * @template-extends AttributeArgument<T>
 */
final class InnerAttributeArgument extends AttributeArgument
{
    /**
     * @param T $value
     * @param Location $location
     */
    public function __construct(
        AttributeMetadata $value,
        Location $location,
    ) {
        parent::__construct($value, $location);
    }

    /**
     * @return T
     * @throws \Throwable
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function eval(): mixed
    {
        try {
            return $this->value->newInstance();
        } catch (SpecificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw SpecificationException::create($e, $this->location->file, $this->location->line);
        }
    }
}
