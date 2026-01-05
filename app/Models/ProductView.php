<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    use HasFactory;

    protected $table = 'product_views';

    protected $fillable = [
        'product_id',
        'account_id',
        'session_id',
        'ip',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope for user (account or session)
     */
    public function scopeForUser($query, $accountId = null, $sessionId = null)
    {
        if ($accountId) {
            return $query->where('account_id', $accountId);
        }

        return $query->where('session_id', $sessionId);
    }

    /**
     * Get recently viewed products for user
     */
    public static function getRecentForUser($accountId = null, $sessionId = null, $limit = 10)
    {
        $query = static::with('product')
            ->forUser($accountId, $sessionId)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->orderByDesc('viewed_at')
            ->limit($limit);

        return $query->get()->pluck('product')->filter();
    }
}
