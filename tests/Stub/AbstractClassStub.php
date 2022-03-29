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

#[Invariant('is_string("AbstractClassStub")')]
abstract class AbstractClassStub
{
    #[Verify('is_string("AbstractClassStub::publicMethod()")')]
    #[Ensure('is_string("AbstractClassStub::publicMethod()")')]
    abstract public function publicMethod(): void;

    #[Verify('is_string("AbstractClassStub::protectedMethod()")')]
    #[Ensure('is_string("AbstractClassStub::protectedMethod()")')]
    abstract protected function protectedMethod(): void;

    #[Verify('is_string("AbstractClassStub::privateMethod()")')]
    #[Ensure('is_string("AbstractClassStub::privateMethod()")')]
    private function privateMethod(): void
    {
    }
}
