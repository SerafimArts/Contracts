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

#[Invariant('is_string("ChildClassStub")')]
class ChildClassStub extends AbstractClassStub
{
    #[Verify('is_string("ChildClassStub::publicMethod()")')]
    #[Ensure('is_string("ChildClassStub::publicMethod()")')]
    public function publicMethod(): void
    {
    }

    #[Verify('is_string("ChildClassStub::protectedMethod()")')]
    #[Ensure('is_string("ChildClassStub::protectedMethod()")')]
    protected function protectedMethod(): void
    {
    }

    #[Verify('is_string("ChildClassStub::privateMethod()")')]
    #[Ensure('is_string("ChildClassStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
