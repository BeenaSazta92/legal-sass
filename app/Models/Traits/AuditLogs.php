<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait AuditLogs
{
    /**
     * Log an action to audit_logs table
     *
     * @param string $action
     * @param array|null $metadata
     */
    public function logActivity(string $action, array $metadata = null)
    {
        AuditLog::create([
            'firm_id' => $this->firm_id ?? Auth::user()?->firm_id,
            'user_id' => Auth::id() ?? null,
            'action' => $action,
            'entity_type' => get_class($this),
            'entity_id' => $this->id ?? null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Boot the trait to automatically log model events
     */
    public static function bootAuditLogs()
    {
        static::created(function ($model) {
            $model->logActivity('created', $model->toArray());
        });

        static::updated(function ($model) {
            $model->logActivity('updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->toArray());
        });
    }
}