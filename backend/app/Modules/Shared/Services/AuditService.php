<?php

namespace Modules\Shared\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Audit Service
 *
 * Centralized audit logging service.
 * Logs: action, user, entity, old/new values, IP, user agent, timestamp.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class AuditService
{
    /**
     * Log an audit event
     *
     * @param string $module Module name
     * @param string $entityType Entity type (e.g., 'Wallet', 'Payment')
     * @param string|int|null $entityId Entity ID
     * @param string $action Action performed (e.g., 'created', 'updated', 'deleted')
     * @param array $oldValues Old values (before change)
     * @param array $newValues New values (after change)
     * @param Request|null $request Request object for IP and user agent
     * @param int|null $userId User ID (if not from request)
     * @return void
     */
    public function log(
        string $module,
        string $entityType,
        string|int|null $entityId,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null,
        ?int $userId = null
    ): void {
        if (!config('shared.audit.enabled', false)) {
            return;
        }

        $userId = $userId ?? ($request?->user()?->id ?? auth()->id());
        $ipAddress = $request?->ip() ?? request()->ip();
        $userAgent = $request?->userAgent() ?? request()->userAgent();

        // Skip logging reads if configured
        if (!$this->shouldLogAction($action)) {
            return;
        }

        DB::table('audit_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'module' => strtolower($module),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log entity creation
     *
     * @param string $module
     * @param string $entityType
     * @param string|int $entityId
     * @param array $attributes
     * @param Request|null $request
     * @return void
     */
    public function logCreation(
        string $module,
        string $entityType,
        string|int $entityId,
        array $attributes,
        ?Request $request = null
    ): void {
        $this->log($module, $entityType, $entityId, 'created', [], $attributes, $request);
    }

    /**
     * Log entity update
     *
     * @param string $module
     * @param string $entityType
     * @param string|int $entityId
     * @param array $oldValues
     * @param array $newValues
     * @param Request|null $request
     * @return void
     */
    public function logUpdate(
        string $module,
        string $entityType,
        string|int $entityId,
        array $oldValues,
        array $newValues,
        ?Request $request = null
    ): void {
        $this->log($module, $entityType, $entityId, 'updated', $oldValues, $newValues, $request);
    }

    /**
     * Log entity deletion
     *
     * @param string $module
     * @param string $entityType
     * @param string|int $entityId
     * @param array $oldValues
     * @param Request|null $request
     * @return void
     */
    public function logDeletion(
        string $module,
        string $entityType,
        string|int $entityId,
        array $oldValues,
        ?Request $request = null
    ): void {
        $this->log($module, $entityType, $entityId, 'deleted', $oldValues, [], $request);
    }

    /**
     * Check if action should be logged
     *
     * @param string $action
     * @return bool
     */
    private function shouldLogAction(string $action): bool
    {
        $logReads = config('shared.audit.log_reads', false);

        // Always log write operations
        if (in_array($action, ['created', 'updated', 'deleted'], true)) {
            return true;
        }

        // Log reads only if configured
        if (in_array($action, ['viewed', 'read', 'retrieved'], true)) {
            return $logReads;
        }

        return true;
    }
}

