<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'action',
        'model_type',
        'model_id',
        'account_id',
        'description',
        'old_data',
        'new_data',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    /**
     * Log an activity
     */
    public static function log(string $action, $model, ?string $description = null, ?array $oldData = null, ?array $newData = null): self
    {
        return static::create([
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
            'account_id' => auth()->id(),
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
