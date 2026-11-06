<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action in the system audit trail.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        string $module,
        ?string $description = null,
        ?Model $record = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $branchId = null
    ): AuditLog {
        $user = Auth::user();

        if ($branchId === null) {
            $branchId = session('kerefa_active_branch_id', $user?->branch_id);
        }

        return AuditLog::create([
            'user_id' => $user?->id,
            'branch_id' => $branchId,
            'action' => $action,
            'module' => $module,
            'record_id' => $record?->getKey(),
            'record_type' => $record ? get_class($record) : null,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
