<?php

namespace Modules\HealthCheck\ValueObjects;

/**
 * Healthy Status Value Object
 *
 * Represents whether a check is healthy or unhealthy
 */
final class HealthyStatus
{
    private const HEALTHY = 'healthy';
    private const UNHEALTHY = 'unhealthy';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function healthy(): self
    {
        return new self(self::HEALTHY);
    }

    public static function unhealthy(): self
    {
        return new self(self::UNHEALTHY);
    }

    public function isHealthy(): bool
    {
        return $this->value === self::HEALTHY;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(HealthyStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

