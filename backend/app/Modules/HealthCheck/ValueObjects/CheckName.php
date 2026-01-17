<?php

namespace Modules\HealthCheck\ValueObjects;

use InvalidArgumentException;

/**
 * Check Name Value Object
 *
 * Represents the name of a health check
 */
final class CheckName
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Check name cannot be empty');
        }

        $this->value = $value;
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public static function application(): self
    {
        return new self('application');
    }

    public static function database(): self
    {
        return new self('database');
    }

    public static function cache(): self
    {
        return new self('cache');
    }

    public static function redis(): self
    {
        return new self('redis');
    }

    public static function storage(): self
    {
        return new self('storage');
    }

    public static function queue(): self
    {
        return new self('queue');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(CheckName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

