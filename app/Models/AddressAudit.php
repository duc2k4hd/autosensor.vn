<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressAudit extends Model
{
    use HasFactory;

    protected $table = 'address_audits';

    protected $fillable = [
        'address_id',
        'performed_by',
        'action',
        'description',
        'changes',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function performer()
    {
        return $this->belongsTo(Account::class, 'performed_by');
    }
}
