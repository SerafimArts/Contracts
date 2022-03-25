<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Attribute;

use JetBrains\PhpStorm\Language;
use Serafim\Contracts\Exception\PostconditionException;

/**
 * Specifies postcondition that apply to the annotated method. The annotated
 * method must establish its postconditions if and only if the preconditions
 * were satisfied.
 *
 * When checking of contracts is enabled, postconditions are checked at method
 * exit, when the method exits normally, of the and throw a
 * {@see PostconditionException} when they are violated. Postconditions are not
 * checked when the method exits by throwing an exception.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Ensure extends Contract
{
    /**
     * The postcondition that must be met by the annotated method. The
     * expression must be valid PHP code and can reference all things visible
     * to the class, as well as the method's arguments. Since postconditions
     * may need to refer to the old value of an expression and the value
     * returned by the annotated method, the following extensions are allowed:
     *
     * The keyword {@see $result} refers to the value returned from the method,
     * if any. It is an error to have a method parameter named {@see $result}.
     *
     * @psalm-taint-sink eval $expr
     * @param non-empty-string $expr
     */
    public function __construct(
        #[Language('PHP')]
        public readonly string $expr
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->expr;
    }
}
