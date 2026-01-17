<?php

namespace Modules\HealthCheck\DTOs;

/**
 * Basic Health Data Transfer Object
 *
 * Simplified health status for basic checks
 */
final class BasicHealthDTO
{
    public string $status;
    public string $timestamp;
    public string $service;
    public string $environment;

    public function __construct(
        string $status,
        string $timestamp,
        string $service,
        string $environment
    ) {
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->service = $service;
        $this->environment = $environment;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'service' => $this->service,
            'environment' => $this->environment,
        ];
    }
}

