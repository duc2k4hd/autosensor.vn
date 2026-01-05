<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_comments' => $this->resource['total_comments'] ?? 0,
            'average_rating' => $this->resource['average_rating'] ?? 0,
            'star_1_count' => $this->resource['star_1_count'] ?? 0,
            'star_2_count' => $this->resource['star_2_count'] ?? 0,
            'star_3_count' => $this->resource['star_3_count'] ?? 0,
            'star_4_count' => $this->resource['star_4_count'] ?? 0,
            'star_5_count' => $this->resource['star_5_count'] ?? 0,
        ];
    }
}
