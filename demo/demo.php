<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Serafim\Contracts\Runtime;
use Serafim\Contracts\Demo\Account;

require __DIR__ . '/../vendor/autoload.php';

Runtime::enable();
Runtime::listen('Serafim\Contracts\Demo');

$account = new Account();
$account->deposit(-42);