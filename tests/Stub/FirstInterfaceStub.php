<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Tests\Stub;

use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;

#[Invariant('is_string("FirstInterfaceStub")')]
interface FirstInterfaceStub extends SecondInterfaceStub
{
    #[Verify('is_string("FirstInterfaceStub::publicMethod()")')]
    #[Ensure('is_string("FirstInterfaceStub::publicMethod()")')]
    public function publicMethod(): void;
}
