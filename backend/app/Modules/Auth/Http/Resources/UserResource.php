<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Formats user data for API responses.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'name' => $this->name,
            'picture' => $this->picture,
            'emailVerified' => $this->email_verified_at !== null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}