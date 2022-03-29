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

#[Invariant('is_string("FirstTraitStub")')]
trait FirstTraitStub
{
    use SecondTraitStub;

    #[Verify('is_string("FirstTraitStub::publicMethod()")')]
    #[Ensure('is_string("FirstTraitStub::publicMethod()")')]
    abstract public function publicMethod(): void;

    #[Verify('is_string("FirstTraitStub::protectedMethod()")')]
    #[Ensure('is_string("FirstTraitStub::protectedMethod()")')]
    abstract protected function protectedMethod(): void;

    #[Verify('is_string("FirstTraitStub::privateMethod()")')]
    #[Ensure('is_string("FirstTraitStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
