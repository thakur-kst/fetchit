<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DBCore\Models\Core\User;
use Modules\GmailAccounts\Models\GmailAccount;

/**
 * Order Model
 * 
 * Represents a parsed order from Gmail emails.
 */
class Order extends Model
{
    protected $table = 'orders';
    
    protected $primaryKey = 'id';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    protected $fillable = [
        'user_id',
        'gmail_account_id',
        'email_id',
        'order_id',
        'vendor',
        'status',
        'subject',
        'total_amount',
        'order_date',
        'delivery_date',
        'items',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'items' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    /**
     * Get the Gmail account associated with the order
     */
    public function gmailAccount(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class, 'gmail_account_id', 'id');
    }

    /**
     * Scope: Filter by vendor
     */
    public function scopeVendor($query, string $vendor)
    {
        return $query->where('vendor', $vendor);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by multiple statuses
     */
    public function scopeStatuses($query, array $statuses)
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Scope: Filter by category (in metadata)
     */
    public function scopeCategory($query, string $category)
    {
        return $query->whereRaw("metadata->>'category' = ?", [$category]);
    }

    /**
     * Scope: Filter by multiple categories
     */
    public function scopeCategories($query, array $categories)
    {
        return $query->where(function ($q) use ($categories) {
            foreach ($categories as $category) {
                $q->orWhereRaw("metadata->>'category' = ?", [$category]);
            }
        });
    }

    /**
     * Scope: Filter by domain (replyTo in metadata)
     */
    public function scopeDomain($query, string $domain)
    {
        return $query->whereRaw("metadata->>'replyTo' LIKE ?", ["%{$domain}%"]);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, ?string $startDate = null, ?string $endDate = null)
    {
        if ($startDate) {
            $query->where('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('order_date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope: Search in order_id, subject, items
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_id', 'LIKE', "%{$search}%")
              ->orWhere('subject', 'LIKE', "%{$search}%")
              ->orWhereRaw("items::text LIKE ?", ["%{$search}%"]);
        });
    }
}
