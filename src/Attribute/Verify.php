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
use Serafim\Contracts\Exception\PreconditionException;

/**
 * Specifies precondition that apply to the annotated method. Callers must
 * establish the preconditions of methods they call.
 *
 * When checking of contracts is enabled, precondition are checked at method
 * entry and throw a {@see PreconditionException} when it is violated.
 *
 * @Annotation
 * @Target({ "METHOD" })
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Verify extends Contract
{
    /**
     * The precondition expression that must be met by the annotated method.
     * The expression must be valid PHP code and can reference all things
     * visible to every caller of the method, as well as the method's arguments.
     *
     * Expression may also reference things that are not visible to the caller,
     * such as private fields when the method is public, but this is considered
     * bad style.
     *
     * @var string
     */
    public $value;

    /**
     * @param string $value
     */
    public function __construct(
        #[Language('PHP')]
        string $value
    ) {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->value;
    }
}