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

#[Invariant('is_string("FinalClassStub")')]
final class FinalClassStub extends ChildClassStub implements
    FirstInterfaceStub,
    SecondInterfaceStub
{
    use FirstTraitStub;
    use SecondTraitStub;

    #[Verify('is_string("FinalClassStub::publicMethod()")')]
    #[Ensure('is_string("FinalClassStub::publicMethod()")')]
    public function publicMethod(): void
    {
    }

    #[Verify('is_string("FinalClassStub::protectedMethod()")')]
    #[Ensure('is_string("FinalClassStub::protectedMethod()")')]
    protected function protectedMethod(): void
    {
    }

    #[Verify('is_string("FinalClassStub::privateMethod()")')]
    #[Ensure('is_string("FinalClassStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
