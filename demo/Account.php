<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Demo;

use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;

class Account
{
    /**
     * @var positive-int|0
     */
    #[Invariant('$this->balance >= 0', 'Balance can not be less than 0')]
    protected int $balance = 0;

    /**
     * Deposits fixed amount of money to the account.
     *
     * @param positive-int $amount
     */
    #[Verify('$amount > 0')]
    #[Ensure('$this->balance === $old->balance + $amount')]
    public function deposit(int $amount): void
    {
        $this->balance += $amount;
    }

    /**
     * Withdraw amount of money from account.
     *
     * @param positive-int $amount
     */
    #[Verify('$amount <= $this->balance')]
    #[Verify('$amount > 0')]
    #[Ensure('$this->balance === $old->balance - $amount')]
    public function withdraw(int $amount): void
    {
        $this->balance -= $amount;
    }

    /**
     * Returns current balance.
     *
     * @return positive-int|0
     */
    #[Ensure('$result === $this->balance')]
    public function getBalance(): int
    {
        return $this->balance;
    }
}
