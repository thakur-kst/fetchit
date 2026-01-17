<?php

namespace Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Services\AuditService;

/**
 * Auditable Trait
 *
 * Trait for models that should be audited.
 * Provides audit() method for manual logging.
 *
 * @package Modules\Shared\Traits
 * @version 1.0.0
 */
trait Auditable
{
    /**
     * Log an audit event for this model
     *
     * @param string $action
     * @param array $oldValues
     * @param array $newValues
     * @return void
     */
    public function audit(string $action, array $oldValues = [], array $newValues = []): void
    {
        $module = $this->getModuleName();
        $entityType = class_basename($this);
        $entityId = $this->getKey();

        app(AuditService::class)->log(
            $module,
            $entityType,
            $entityId,
            $action,
            $oldValues,
            $newValues
        );
    }

    /**
     * Get module name from model namespace
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        $namespace = get_class($this);
        if (preg_match('/Modules\\\\(\w+)\\\\/', $namespace, $matches)) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }

    /**
     * Boot the auditable trait
     *
     * @return void
     */
    protected static function bootAuditable(): void
    {
        // Log creation
        static::created(function (Model $model) {
            if (config('shared.audit.enabled', false)) {
                $model->audit('created', [], $model->getAttributes());
            }
        });

        // Log update
        static::updated(function (Model $model) {
            if (config('shared.audit.enabled', false)) {
                $model->audit('updated', $model->getOriginal(), $model->getChanges());
            }
        });

        // Log deletion
        static::deleted(function (Model $model) {
            if (config('shared.audit.enabled', false)) {
                $model->audit('deleted', $model->getAttributes(), []);
            }
        });
    }
}

