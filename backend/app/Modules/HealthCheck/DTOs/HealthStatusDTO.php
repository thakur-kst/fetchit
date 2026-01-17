<?php

namespace Modules\HealthCheck\DTOs;

/**
 * Health Status Data Transfer Object
 *
 * Transfers health status data between layers
 */
final class HealthStatusDTO
{
    public string $status;
    public string $timestamp;
    public string $service;
    public string $environment;
    public array $checks;

    public function __construct(
        string $status,
        string $timestamp,
        string $service,
        string $environment,
        array $checks
    ) {
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->service = $service;
        $this->environment = $environment;
        $this->checks = $checks;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'service' => $this->service,
            'environment' => $this->environment,
            'checks' => $this->checks,
        ];
    }
}

