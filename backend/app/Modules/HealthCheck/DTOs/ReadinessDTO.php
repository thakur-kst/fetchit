<?php

namespace Modules\HealthCheck\DTOs;

/**
 * Readiness Data Transfer Object
 *
 * Represents system readiness for traffic
 */
final class ReadinessDTO
{
    public bool $ready;
    public string $timestamp;
    public array $checks;

    public function __construct(
        bool $ready,
        string $timestamp,
        array $checks
    ) {
        $this->ready = $ready;
        $this->timestamp = $timestamp;
        $this->checks = $checks;
    }

    public function toArray(): array
    {
        return [
            'ready' => $this->ready,
            'timestamp' => $this->timestamp,
            'checks' => $this->checks,
        ];
    }
}

