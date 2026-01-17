<?php

namespace Modules\HealthCheck\DTOs;

/**
 * Liveness Data Transfer Object
 *
 * Represents system liveness status
 */
final class LivenessDTO
{
    public bool $alive;
    public string $timestamp;

    public function __construct(
        bool $alive,
        string $timestamp
    ) {
        $this->alive = $alive;
        $this->timestamp = $timestamp;
    }

    public function toArray(): array
    {
        return [
            'alive' => $this->alive,
            'timestamp' => $this->timestamp,
        ];
    }
}

