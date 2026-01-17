<?php

namespace Modules\GmailSync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GmailAccounts\Models\GmailAccount;

/**
 * Gmail Sync Job Model
 * 
 * Tracks Gmail sync job progress for polling.
 */
class GmailSyncJob extends Model
{
    protected $table = 'gmail_sync_jobs';
    
    protected $primaryKey = 'id';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    protected $fillable = [
        'gmail_account_id',
        'total_emails',
        'processed_emails',
        'new_orders',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_emails' => 'integer',
        'processed_emails' => 'integer',
        'new_orders' => 'integer',
    ];

    /**
     * Get the Gmail account that owns this sync job
     */
    public function gmailAccount(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class, 'gmail_account_id', 'id');
    }

    /**
     * Check if sync is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed' || 
               ($this->status === 'processing' && $this->processed_emails >= $this->total_emails);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_emails' => $this->total_emails,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Increment processed emails
     */
    public function incrementProcessed(bool $isOrder = false): void
    {
        $this->increment('processed_emails');
        
        if ($isOrder) {
            $this->increment('new_orders');
        }

        // Auto-complete if all emails processed
        if ($this->processed_emails >= $this->total_emails && $this->status === 'processing') {
            $this->markAsCompleted();
        }
    }
}
