<?php

namespace App\Services;

use App\Models\Post;

class SeoService
{
    /**
     * Đánh giá SEO score cho post
     */
    public function evaluateSeoScore(Post $post): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $suggestions = [];

        // Kiểm tra title (20 điểm)
        if (! empty($post->title)) {
            $titleLength = mb_strlen($post->title);
            if ($titleLength >= 30 && $titleLength <= 60) {
                $score += 20;
            } elseif ($titleLength > 0) {
                $score += 10;
                if ($titleLength < 30) {
                    $issues[] = 'Tiêu đề quá ngắn (nên từ 30-60 ký tự)';
                } elseif ($titleLength > 60) {
                    $issues[] = 'Tiêu đề quá dài (nên từ 30-60 ký tự)';
                }
            }
        } else {
            $issues[] = 'Thiếu tiêu đề';
        }

        // Kiểm tra meta_title (15 điểm)
        if (! empty($post->meta_title)) {
            $metaTitleLength = mb_strlen($post->meta_title);
            if ($metaTitleLength >= 30 && $metaTitleLength <= 60) {
                $score += 15;
            } else {
                $score += 7;
                if ($metaTitleLength < 30) {
                    $issues[] = 'Meta title quá ngắn (nên từ 30-60 ký tự)';
                } elseif ($metaTitleLength > 60) {
                    $issues[] = 'Meta title quá dài (nên từ 30-60 ký tự)';
                }
            }
        } else {
            $suggestions[] = 'Nên thêm meta title để tối ưu SEO';
        }

        // Kiểm tra meta_description (15 điểm)
        if (! empty($post->meta_description)) {
            $metaDescLength = mb_strlen($post->meta_description);
            if ($metaDescLength >= 120 && $metaDescLength <= 160) {
                $score += 15;
            } else {
                $score += 7;
                if ($metaDescLength < 120) {
                    $issues[] = 'Meta description quá ngắn (nên từ 120-160 ký tự)';
                } elseif ($metaDescLength > 160) {
                    $issues[] = 'Meta description quá dài (nên từ 120-160 ký tự)';
                }
            }
        } else {
            $suggestions[] = 'Nên thêm meta description để tối ưu SEO';
        }

        // Kiểm tra slug (10 điểm)
        if (! empty($post->slug)) {
            if (mb_strlen($post->slug) <= 100) {
                $score += 10;
            } else {
                $score += 5;
                $issues[] = 'Slug quá dài (nên dưới 100 ký tự)';
            }
        } else {
            $issues[] = 'Thiếu slug';
        }

        // Kiểm tra content (20 điểm)
        if (! empty($post->content)) {
            $contentLength = mb_strlen(strip_tags($post->content));
            if ($contentLength >= 300) {
                $score += 20;
            } elseif ($contentLength >= 150) {
                $score += 10;
                $suggestions[] = 'Nội dung nên dài hơn 300 ký tự để tốt cho SEO';
            } else {
                $issues[] = 'Nội dung quá ngắn (nên từ 300 ký tự trở lên)';
            }
        } else {
            $issues[] = 'Thiếu nội dung';
        }

        // Kiểm tra excerpt (10 điểm)
        if (! empty($post->excerpt)) {
            $excerptLength = mb_strlen($post->excerpt);
            if ($excerptLength >= 120 && $excerptLength <= 200) {
                $score += 10;
            } else {
                $score += 5;
                if ($excerptLength < 120) {
                    $suggestions[] = 'Tóm tắt nên từ 120-200 ký tự';
                }
            }
        } else {
            $suggestions[] = 'Nên thêm tóm tắt bài viết';
        }

        // Kiểm tra tags (10 điểm)
        if (! empty($post->tag_ids) && is_array($post->tag_ids) && count($post->tag_ids) > 0) {
            $score += 10;
        } else {
            $suggestions[] = 'Nên thêm tags để cải thiện SEO';
        }

        // Xác định level
        $level = 'poor';
        if ($score >= 80) {
            $level = 'excellent';
        } elseif ($score >= 60) {
            $level = 'good';
        } elseif ($score >= 40) {
            $level = 'fair';
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'level' => $level,
            'issues' => $issues,
            'suggestions' => $suggestions,
        ];
    }
}
