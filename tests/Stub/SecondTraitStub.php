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

#[Invariant('is_string("SecondTraitStub")')]
trait SecondTraitStub
{
    #[Verify('is_string("SecondTraitStub::publicMethod()")')]
    #[Ensure('is_string("SecondTraitStub::publicMethod()")')]
    public function publicMethod(): void
    {
    }

    #[Verify('is_string("SecondTraitStub::protectedMethod()")')]
    #[Ensure('is_string("SecondTraitStub::protectedMethod()")')]
    protected function protectedMethod(): void
    {
    }

    #[Verify('is_string("SecondTraitStub::privateMethod()")')]
    #[Ensure('is_string("SecondTraitStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
