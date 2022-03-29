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

#[Invariant('is_string("EnumStub")')]
enum EnumStub implements FirstInterfaceStub, SecondInterfaceStub
{
    #[Verify('is_string("EnumStub::publicMethod()")')]
    #[Ensure('is_string("EnumStub::publicMethod()")')]
    public function publicMethod(): void
    {
    }

    #[Verify('is_string("EnumStub::protectedMethod()")')]
    #[Ensure('is_string("EnumStub::protectedMethod()")')]
    protected function protectedMethod(): void
    {
    }

    #[Verify('is_string("EnumStub::privateMethod()")')]
    #[Ensure('is_string("EnumStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
