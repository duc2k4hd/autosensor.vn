<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Comment;

class CommentPolicy
{
    /**
     * Người dùng không được reply comment
     */
    public function reply(Account $account, Comment $comment): bool
    {
        // Chỉ admin mới được reply
        return $account->role === 'admin';
    }

    /**
     * Admin có thể reply
     */
    public function adminReply(Account $account, Comment $comment): bool
    {
        return $account->role === 'admin';
    }

    /**
     * Admin có thể duyệt comment
     */
    public function approve(Account $account, Comment $comment): bool
    {
        return $account->role === 'admin';
    }

    /**
     * Admin có thể xóa comment
     */
    public function delete(Account $account, Comment $comment): bool
    {
        return $account->role === 'admin';
    }
}
