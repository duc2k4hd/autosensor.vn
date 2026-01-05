<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $table = 'favorites';

    protected $fillable = [
        'product_id',
        'account_id',
        'session_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'product_id' => 'integer',
        'session_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeOfOwner($query, $accountId = null, $sessionId = null)
    {
        if ($accountId) {
            return $query->where('account_id', $accountId);
        }

        return $query->whereNull('account_id')->where('session_id', $sessionId);
    }

    /**
     * Check if a product is favorited by account or session.
     */
    public static function isFavorited($productId, $accountId = null, $sessionId = null): bool
    {
        $q = static::where('product_id', $productId);
        if ($accountId) {
            $q->where('account_id', $accountId);
        }
        if ($sessionId) {
            $q->orWhere('session_id', $sessionId);
        }

        return $q->exists();
    }
}
