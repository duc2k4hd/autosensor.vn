<?php

namespace App\Helpers;

use App\Models\Comment;

class CommentRatingHelper
{
    /**
     * Tính rating statistics cho product/post
     */
    public static function calculateRatingStats(string $type, int $objectId): array
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
     * Format rating để hiển thị
     */
    public static function formatRating(float $rating): string
    {
        return number_format($rating, 1);
    }

    /**
     * Tính phần trăm rating
     */
    public static function calculateRatingPercentage(int $starCount, int $totalComments): float
    {
        if ($totalComments === 0) {
            return 0;
        }

        return round(($starCount / $totalComments) * 100, 1);
    }
}
