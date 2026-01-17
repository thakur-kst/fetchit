<?php

namespace Modules\Auth\DTOs;

use Modules\DBCore\Models\Core\Auth;

final class AuthDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $createdAt
    ) {
    }

    public static function fromModel(Auth $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            createdAt: $model->created_at->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
        ];
    }
}
