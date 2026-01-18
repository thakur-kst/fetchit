<?php

namespace Modules\GmailAccounts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Gmail Account Resource
 *
 * Formats Gmail account data for API responses.
 */
class GmailAccountResource extends JsonResource
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
            'id' => $this->id,
            'email' => $this->email,
            'displayName' => $this->display_name,
            'pictureUrl' => $this->picture_url,
            'isActive' => $this->is_active,
            'tokenType' => $this->token_type,
            'scope' => $this->scope,
            'tokenExpiresAt' => $this->token_expires_at?->toIso8601String(),
            'lastSyncedAt' => $this->last_synced_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
