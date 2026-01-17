<?php

namespace Modules\GmailSync\Services;

use Google_Client;
use Google_Service_Gmail;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailSync\Models\GmailSyncJob;
use Modules\Orders\Models\Order;
use Modules\GmailSync\Jobs\ParseEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Gmail Sync Service
 * 
 * Handles Gmail email fetching and sync orchestration.
 */
class GmailSyncService
{
    /**
     * Fetch all Gmail message IDs for an account
     * 
     * @param GmailAccount $account
     * @return array Message IDs
     */
    public function fetchAllGmailMessageIds(GmailAccount $account): array
    {
        try {
            $client = new Google_Client();
            $client->setAccessToken([
                'access_token' => $account->access_token,
                'expires_in' => $account->token_expires_at ? $account->token_expires_at->diffInSeconds(now()) : 3600,
            ]);

            $service = new Google_Service_Gmail($client);
            $messageIds = [];
            $pageToken = null;

            // Build query (incremental sync)
            $query = 'category:purchases';
            if ($account->last_synced_at) {
                $date = Carbon::parse($account->last_synced_at)->format('Y/m/d');
                $query .= " after:{$date}"; // Only new emails
            } else {
                $query .= ' newer_than:60d'; // New account: last 60 days
            }

            // Fetch ALL pages
            do {
                $response = $service->users_messages->listUsersMessages('me', [
                    'q' => $query,
                    'maxResults' => 100,
                    'pageToken' => $pageToken,
                ]);

                foreach ($response->getMessages() as $message) {
                    $messageIds[] = $message->getId();
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $messageIds;
        } catch (\Exception $e) {
            Log::error('Error fetching Gmail message IDs', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
            throw $e;
        }
    }

    /**
     * Filter already-synced emails
     * 
     * @param array $messageIds
     * @param string $accountId
     * @return array New message IDs
     */
    public function filterSyncedEmails(array $messageIds, string $accountId): array
    {
        if (empty($messageIds)) {
            return [];
        }

        $existingEmailIds = Order::where('gmail_account_id', $accountId)
            ->whereNotNull('email_id')
            ->whereIn('email_id', $messageIds)
            ->pluck('email_id')
            ->toArray();

        return array_diff($messageIds, $existingEmailIds);
    }

    /**
     * Start sync for an account
     * 
     * @param GmailAccount $account
     * @return GmailSyncJob
     */
    public function startSync(GmailAccount $account): GmailSyncJob
    {
        // Fetch all message IDs
        $allMessageIds = $this->fetchAllGmailMessageIds($account);

        // Filter already-synced
        $newMessageIds = $this->filterSyncedEmails($allMessageIds, $account->id);

        // Create sync job
        $syncJob = GmailSyncJob::create([
            'gmail_account_id' => $account->id,
            'total_emails' => count($newMessageIds),
            'processed_emails' => 0,
            'new_orders' => 0,
            'status' => 'processing',
        ]);

        // Dispatch job for each email
        foreach ($newMessageIds as $messageId) {
            ParseEmailJob::dispatch($account, $messageId, $syncJob->id)
                ->onQueue('email-parsing');
        }

        return $syncJob;
    }
}
