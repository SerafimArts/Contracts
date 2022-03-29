<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata\Info;

enum MethodModifier
{
    /**
     * The final keyword prevents child classes from overriding a method or
     * constant by prefixing the definition with final. If the class itself is
     * being defined final then it cannot be extended.
     */
    case FINAL;

    /**
     * Methods defined as abstract simply declare the method's signature;
     * They cannot define the implementation.
     */
    case ABSTRACT;

    /**
     * Declaring class properties or methods as static makes them accessible
     * without needing an instantiation of the class. These can also be accessed
     * statically within an instantiated class object.
     */
    case STATIC;

    /**
     * This is "virtual" method modifier that specifies that the given method
     * cannot contain the `clone` keyword. For example, such methods can be
     * defined in the {@see \Exception} class or {@see \Throwable} interface.
     */
    case NO_CLONE;
}
