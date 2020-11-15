<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Demo;

use Serafim\Contracts\Attribute\Verify;

class Test
{
    #[Verify('true')]
    public static function error()
    {
        throw new \LogicException('asdasd');
    }
}