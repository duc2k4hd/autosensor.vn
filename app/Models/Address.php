<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'account_id',
        'full_name',
        'phone_number',
        'detail_address',
        'ward',
        'district',
        'province',
        'province_code',
        'district_code',
        'ward_code',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'address_type',
        'notes',
        'is_default',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'province_code' => 'integer',
        'district_code' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Lọc theo các điều kiện trên trang quản trị.
     */
    public function scopeFilter($query, array $filters = [])
    {
        return $query
            ->when($filters['account_id'] ?? null, function ($q, $accountId) {
                $q->where('account_id', $accountId);
            })
            ->when($filters['full_name'] ?? null, function ($q, $fullName) {
                $q->where('full_name', 'like', '%'.$fullName.'%');
            })
            ->when($filters['phone_number'] ?? null, function ($q, $phone) {
                $q->where('phone_number', 'like', '%'.$phone.'%');
            })
            ->when($filters['province'] ?? null, function ($q, $province) {
                $q->where('province', 'like', '%'.$province.'%');
            })
            ->when($filters['district'] ?? null, function ($q, $district) {
                $q->where('district', 'like', '%'.$district.'%');
            })
            ->when(array_key_exists('address_type', $filters) && $filters['address_type'] !== null, function ($q) use ($filters) {
                $q->where('address_type', $filters['address_type']);
            })
            ->when(array_key_exists('is_default', $filters) && $filters['is_default'] !== null, function ($q) use ($filters) {
                $q->where('is_default', (bool) $filters['is_default']);
            });
    }
}
