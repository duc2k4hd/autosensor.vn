<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Comment;

class CommentService
{
    /**
     * Tạo comment mới
     */
    public function create(array $data, ?Account $account = null): Comment
    {
        $comment = new Comment;
        $comment->commentable_type = $data['type'];
        $comment->commentable_id = $data['object_id'];
        $comment->name = $data['name'];
        $comment->email = $data['email'];
        $comment->content = $this->cleanContent($data['content']);
        $comment->rating = $data['rating'] ?? null;
        $comment->is_approved = false;
        $comment->ip = request()->ip();
        $comment->user_agent = request()->userAgent();
        $comment->session_id = session()->getId();

        if ($account) {
            $comment->account_id = $account->id;
        }

        $comment->save();

        return $comment;
    }

    /**
     * Admin reply comment
     */
    public function reply(int $commentId, string $replyContent, Account $admin): Comment
    {
        $parentComment = Comment::findOrFail($commentId);

        // Tự động duyệt comment khi admin reply
        if (! $parentComment->is_approved) {
            $parentComment->is_approved = true;
            $parentComment->save();
        }

        // Xóa reply cũ nếu có
        $oldReply = $parentComment->adminReply()->first();
        if ($oldReply) {
            $oldReply->delete();
        }

        // Tạo reply mới
        $reply = new Comment;
        $reply->parent_id = $parentComment->id;
        $reply->commentable_type = $parentComment->commentable_type;
        $reply->commentable_id = $parentComment->commentable_id;
        $reply->account_id = $admin->id;
        $reply->content = $this->cleanContent($replyContent);
        $reply->is_approved = true; // Reply của admin tự động approved
        $reply->ip = request()->ip();
        $reply->user_agent = request()->userAgent();
        $reply->save();

        return $reply;
    }

    /**
     * Cập nhật reply
     */
    public function updateReply(int $replyId, string $replyContent, Account $admin): Comment
    {
        $reply = Comment::findOrFail($replyId);

        if ($reply->parent_id === null) {
            throw new \Exception('Comment này không phải là reply');
        }

        $reply->content = $this->cleanContent($replyContent);
        $reply->save();

        return $reply;
    }

    /**
     * Xóa reply
     */
    public function deleteReply(int $replyId): bool
    {
        $reply = Comment::findOrFail($replyId);

        if ($reply->parent_id === null) {
            throw new \Exception('Comment này không phải là reply');
        }

        return $reply->delete();
    }

    /**
     * Duyệt comment
     */
    public function approve(int $commentId): Comment
    {
        $comment = Comment::findOrFail($commentId);
        $comment->is_approved = true;
        $comment->save();

        return $comment;
    }

    /**
     * Hủy duyệt comment
     */
    public function reject(int $commentId): Comment
    {
        $comment = Comment::findOrFail($commentId);
        $comment->is_approved = false;
        $comment->save();

        return $comment;
    }

    /**
     * Xóa comment
     */
    public function delete(int $commentId): bool
    {
        $comment = Comment::findOrFail($commentId);

        // Xóa tất cả replies
        $comment->replies()->delete();

        return $comment->delete();
    }

    /**
     * Tính rating statistics cho product/post
     */
    public function calculateRatingStats(string $type, int $objectId): array
    {
        $comments = Comment::where('commentable_type', $type)
            ->where('commentable_id', $objectId)
            ->whereNotNull('rating')
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->selectRaw('
                COUNT(*) as total_comments,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5_count
            ')
            ->first();

        return [
            'total_comments' => (int) ($comments->total_comments ?? 0),
            'average_rating' => round((float) ($comments->average_rating ?? 0), 2),
            'star_1_count' => (int) ($comments->star_1_count ?? 0),
            'star_2_count' => (int) ($comments->star_2_count ?? 0),
            'star_3_count' => (int) ($comments->star_3_count ?? 0),
            'star_4_count' => (int) ($comments->star_4_count ?? 0),
            'star_5_count' => (int) ($comments->star_5_count ?? 0),
        ];
    }

    /**
     * Clean content để chống XSS
     */
    protected function cleanContent(string $content): string
    {
        // Loại bỏ các thẻ script và các thẻ nguy hiểm
        $content = strip_tags($content, '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>');

        // Escape các ký tự đặc biệt
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Decode lại để giữ format HTML hợp lệ
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        return trim($content);
    }
}
