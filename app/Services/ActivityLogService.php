<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Log create action
     */
    public function logCreate(Model $model, ?string $description = null): void
    {
        ActivityLog::log('create', $model, $description ?? 'Tạo mới '.class_basename($model), null, $model->toArray());
    }

    /**
     * Log update action
     */
    public function logUpdate(Model $model, array $oldData, ?string $description = null): void
    {
        ActivityLog::log('update', $model, $description ?? 'Cập nhật '.class_basename($model), $oldData, $model->toArray());
    }

    /**
     * Log delete action
     */
    public function logDelete(Model $model, ?string $description = null): void
    {
        ActivityLog::log('delete', $model, $description ?? 'Xóa '.class_basename($model), $model->toArray(), null);
    }

    /**
     * Log custom action
     */
    public function logAction(string $action, Model $model, ?string $description = null, ?array $data = null): void
    {
        ActivityLog::log($action, $model, $description, null, $data);
    }
}
