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

#[Invariant('is_string("SecondInterfaceStub")')]
interface SecondInterfaceStub
{
    #[Verify('is_string("SecondInterfaceStub::publicMethod()")')]
    #[Ensure('is_string("SecondInterfaceStub::publicMethod()")')]
    public function publicMethod(): void;
}
