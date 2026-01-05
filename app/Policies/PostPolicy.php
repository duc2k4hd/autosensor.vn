<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Post;

class PostPolicy
{
    /**
     * Determine if user can view any posts.
     */
    public function viewAny(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can view the post.
     */
    public function view(Account $account, Post $post): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can create posts.
     */
    public function create(Account $account): bool
    {
        return in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER]);
    }

    /**
     * Determine if user can update the post.
     */
    public function update(Account $account, Post $post): bool
    {
        // Admin có thể sửa tất cả
        if ($account->role === Account::ROLE_ADMIN) {
            return true;
        }

        // Writer chỉ có thể sửa bài viết của chính mình
        if ($account->role === Account::ROLE_WRITER) {
            return $post->created_by === $account->id || $post->account_id === $account->id;
        }

        return false;
    }

    /**
     * Determine if user can delete the post.
     */
    public function delete(Account $account, Post $post): bool
    {
        // Admin có thể xóa tất cả
        if ($account->role === Account::ROLE_ADMIN) {
            return true;
        }

        // Writer chỉ có thể xóa bài viết của chính mình
        if ($account->role === Account::ROLE_WRITER) {
            return $post->created_by === $account->id || $post->account_id === $account->id;
        }

        return false;
    }

    /**
     * Determine if user can restore the post.
     */
    public function restore(Account $account, Post $post): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }

    /**
     * Determine if user can permanently delete the post.
     */
    public function forceDelete(Account $account, Post $post): bool
    {
        return $account->role === Account::ROLE_ADMIN;
    }
}
