<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? asset('clients/assets/img/categories/'.$this->image) : null,
            'thumbnail' => $this->image ? asset('clients/assets/img/categories/thumbs/'.$this->image) : null,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug,
                ];
            }),
            'order' => $this->order,
            'is_active' => $this->is_active,
            'children_count' => $this->whenCounted('children', $this->children()->count()),
            'products_count' => $this->whenCounted('primaryProducts', $this->primaryProducts()->count()),
            'posts_count' => $this->whenCounted('posts', $this->posts()->count()),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
