<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\FlashSale;

class FlashSalePolicy
{
    /**
     * Determine if user can view any flash sales
     */
    public function viewAny(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can view the flash sale
     */
    public function view(Account $account, FlashSale $flashSale): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can create flash sales
     */
    public function create(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can update the flash sale
     */
    public function update(Account $account, FlashSale $flashSale): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can delete the flash sale
     */
    public function delete(Account $account, FlashSale $flashSale): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }
}
