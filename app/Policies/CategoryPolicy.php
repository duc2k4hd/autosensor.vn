<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Category;

class CategoryPolicy
{
    /**
     * Determine if user can view any categories
     */
    public function viewAny(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can view the category
     */
    public function view(Account $account, Category $category): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can create categories
     */
    public function create(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can update the category
     */
    public function update(Account $account, Category $category): bool
    {
        if ($account->role === Account::ROLE_ADMIN) {
            return true;
        }

        if ($account->role === Account::ROLE_WRITER) {
            // Writer can only edit name, image, is_active
            return true;
        }

        return false;
    }

    /**
     * Determine if user can delete any category
     */
    public function deleteAny(Account $account): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }

    /**
     * Determine if user can delete the category
     */
    public function delete(Account $account, Category $category): bool
    {
        if ($account->role === Account::ROLE_ADMIN) {
            // Admin can delete any category (including root)
            return true;
        }

        return false;
    }

    /**
     * Determine if user can delete category tree (including children)
     */
    public function deleteTree(Account $account, Category $category): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }

    /**
     * Determine if user can change slug
     */
    public function changeSlug(Account $account, Category $category): bool
    {
        if ($account->role === Account::ROLE_ADMIN) {
            // Admin can change slug of any category (including root)
            return true;
        }

        return false;
    }

    /**
     * Determine if user can change parent
     */
    public function changeParent(Account $account, Category $category): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }

    /**
     * Determine if user can reorder categories
     */
    public function reorder(Account $account): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }
}
