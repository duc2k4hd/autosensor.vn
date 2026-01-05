<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'login_attempts' => $this->login_attempts,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'last_login' => $this->accountLogs()
                ->where('type', 'login_success')
                ->latest()
                ->first()?->created_at?->toIso8601String(),
            'addresses_count' => $this->whenLoaded('addresses', fn () => $this->addresses->count()),
            'orders_count' => $this->whenLoaded('orders', fn () => $this->orders->count()),
            'favorites_count' => $this->whenLoaded('favorites', fn () => $this->favorites->count()),
        ];
    }
}
