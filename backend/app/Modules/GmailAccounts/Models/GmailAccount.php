<?php

namespace Modules\GmailAccounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DBCore\Models\Core\User;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Gmail Account Model
 * 
 * Represents a linked Gmail account with OAuth tokens.
 */
class GmailAccount extends Model
{
    protected $table = 'gmail_accounts';
    
    protected $primaryKey = 'id';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    protected $fillable = [
        'user_id',
        'email',
        'display_name',
        'picture_url',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'token_expires_at',
        'last_synced_at',
        'is_active',
        'locale',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the Gmail account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    /**
     * Access token accessor - automatically decrypts
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Refresh token accessor - automatically decrypts
     */
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Check if token needs refresh (expires within 5 minutes)
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isBefore(now()->addMinutes(5));
    }
}
