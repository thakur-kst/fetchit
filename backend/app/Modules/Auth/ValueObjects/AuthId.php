<?php

namespace Modules\Auth\ValueObjects;

use Illuminate\Support\Str;

final class AuthId
{
    private string $value;

    private function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('AuthId cannot be empty');
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(AuthId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
