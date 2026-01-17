<?php

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\AuthDTO;
use Modules\DBCore\Models\Core\Auth;
use Modules\Auth\ValueObjects\AuthId;

class AuthApplicationService
{
    public function getAll(): array
    {
        $items = Auth::all();
        return $items->map(fn($item) => AuthDTO::fromModel($item))->toArray();
    }

    public function getById(string $id): ?AuthDTO
    {
        $item = Auth::find($id);
        return $item ? AuthDTO::fromModel($item) : null;
    }

    public function create(string $name): AuthDTO
    {
        $item = Auth::create([
            'id' => AuthId::generate()->value(),
            'name' => $name,
        ]);
        return AuthDTO::fromModel($item);
    }

    public function update(string $id, string $name): ?AuthDTO
    {
        $item = Auth::find($id);
        if (!$item)
            return null;
        $item->update(['name' => $name]);
        return AuthDTO::fromModel($item);
    }

    public function delete(string $id): bool
    {
        $item = Auth::find($id);
        if (!$item)
            return false;
        return $item->delete();
    }

    public function getTestData(): AuthDTO
    {
        return new AuthDTO(
            id: AuthId::generate()->value(),
            name: 'Test Auth',
            createdAt: now()->toIso8601String()
        );
    }
}
