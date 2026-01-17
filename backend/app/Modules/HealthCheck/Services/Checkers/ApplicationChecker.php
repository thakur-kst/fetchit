<?php

namespace Modules\HealthCheck\Services\Checkers;

use Modules\HealthCheck\ValueObjects\CheckName;
use Modules\HealthCheck\ValueObjects\CheckResult;

/**
 * Application Health Checker
 *
 * Checks basic application health and configuration
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class ApplicationChecker implements HealthCheckerInterface
{
    /**
     * Perform application health check
     *
     * Verifies that the application is running and provides
     * basic environment information including PHP version,
     * Laravel version, and configuration status.
     *
     * @return CheckResult Health check result with application info
     */
    public function check(): CheckResult
    {
        return CheckResult::healthy(
            CheckName::application(),
            'Application is running',
            [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'app_key_set' => !empty(config('app.key')),
                'debug_mode' => config('app.debug'),
            ]
        );
    }
}

