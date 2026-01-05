<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'type' => $this->commentable_type,
            'object_id' => $this->commentable_id,
            'name' => $this->name,
            'email' => $this->email,
            'content' => $this->content,
            'rating' => $this->rating,
            'reply_content' => $this->reply_content,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                    'email' => $this->account->email,
                ];
            }),
            'admin_reply' => $this->whenLoaded('adminReply', function () {
                return [
                    'id' => $this->adminReply->id,
                    'content' => $this->adminReply->content,
                    'created_at' => $this->adminReply->created_at?->format('Y-m-d H:i:s'),
                    'admin' => $this->adminReply->account ? [
                        'id' => $this->adminReply->account->id,
                        'name' => $this->adminReply->account->name,
                    ] : null,
                ];
            }),
        ];
    }
}
