<?php

namespace Modules\HealthCheck\ValueObjects;

use DateTime;
use DateTimeImmutable;

/**
 * Timestamp Value Object
 *
 * Represents a point in time
 */
final class Timestamp
{
    private DateTimeImmutable $value;

    private function __construct(DateTimeImmutable $value)
    {
        $this->value = $value;
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public static function from(DateTime|DateTimeImmutable|string $value): self
    {
        if ($value instanceof DateTimeImmutable) {
            return new self($value);
        }

        if ($value instanceof DateTime) {
            return new self(DateTimeImmutable::createFromMutable($value));
        }

        return new self(new DateTimeImmutable($value));
    }

    public function toIso8601String(): string
    {
        return $this->value->format('c');
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function equals(Timestamp $other): bool
    {
        return $this->value == $other->value;
    }

    public function __toString(): string
    {
        return $this->toIso8601String();
    }
}

