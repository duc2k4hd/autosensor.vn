<?php

namespace App\Policies;

use App\Models\Account;

class AccountPolicy
{
    public function viewAny(Account $admin): bool
    {
        return $this->isAdminOrWriter($admin);
    }

    public function view(Account $admin, Account $account): bool
    {
        if (! $this->isAdminOrWriter($admin)) {
            return false;
        }

        // Admin and writer can view any account including themselves
        return true;
    }

    public function create(Account $admin): bool
    {
        // Only admin can create accounts
        return $admin->isAdmin();
    }

    public function update(Account $admin, Account $account): bool
    {
        if (! $this->isAdminOrWriter($admin)) {
            return false;
        }

        // Writer can only update their own account
        if ($admin->isWriter() && $admin->id !== $account->id) {
            return false;
        }

        // Admin can update any account including themselves
        // But they cannot change their own role or some critical fields
        return true;
    }

    public function delete(Account $admin, Account $account): bool
    {
        // Only admin can delete accounts
        if (! $admin->isAdmin()) {
            return false;
        }

        // Cannot delete yourself
        if ($admin->id === $account->id) {
            return false;
        }

        // Admin can delete any account (including other admins)
        return true;
    }

    public function lock(Account $admin, Account $account): bool
    {
        // Only admin can lock accounts
        if (! $admin->isAdmin()) {
            return false;
        }

        // Cannot lock yourself
        if ($admin->id === $account->id) {
            return false;
        }

        // Admin can lock any account (including other admins)
        return true;
    }

    public function unlock(Account $admin, Account $account): bool
    {
        return $this->lock($admin, $account);
    }

    public function ban(Account $admin, Account $account): bool
    {
        return $this->lock($admin, $account);
    }

    public function unban(Account $admin, Account $account): bool
    {
        return $this->lock($admin, $account);
    }

    public function changeRole(Account $admin, Account $account): bool
    {
        // Only admin can change roles
        if (! $admin->isAdmin()) {
            return false;
        }

        // Cannot change your own role
        if ($admin->id === $account->id) {
            return false;
        }

        // Admin can change any account's role (including other admins)
        return true;
    }

    public function resetPassword(Account $admin, Account $account): bool
    {
        return $this->update($admin, $account);
    }

    private function isAdminOrWriter(Account $account): bool
    {
        return in_array($account->role, [
            Account::ROLE_ADMIN,
            Account::ROLE_WRITER,
        ]);
    }
}
