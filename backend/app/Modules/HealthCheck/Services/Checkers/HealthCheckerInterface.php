<?php

namespace Modules\HealthCheck\Services\Checkers;

use Modules\HealthCheck\ValueObjects\CheckResult;

/**
 * Health Checker Interface
 *
 * Contract for all health checkers
 */
interface HealthCheckerInterface
{
    /**
     * Perform the health check
     *
     * @return CheckResult
     */
    public function check(): CheckResult;
}

