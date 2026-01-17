<?php

namespace Modules\GmailSync\Jobs;

use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailSync\Models\GmailSyncJob;
use Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Gmail;

class ParseEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;       // Retry failed jobs 3 times
    public $timeout = 30;    // 30 seconds per job

    public function __construct(
        public GmailAccount $account,
        public string $messageId,
        public string $syncJobId
    ) {}

    public function handle()
    {
        try {
            // 1. Fetch email details from Gmail API
            $emailData = $this->fetchEmailFromGmail();

            // 2. Call parser service
            $response = Http::timeout(20)
                ->post(config('services.parser.url') . '/parse-email', $emailData);

            if (!$response->successful()) {
                throw new \Exception('Parser service failed: ' . $response->body());
            }

            $parsedOrder = $response->json();

            // 3. If not an order email, skip
            if (!$parsedOrder) {
                $this->updateSyncProgress(false);
                return;
            }

            // 4. Save order to database
            Order::create([
                'user_id' => $this->account->user_id,
                'gmail_account_id' => $this->account->id,
                'email_id' => $this->messageId,
                'order_id' => $parsedOrder['orderId'] ?? null,
                'vendor' => $parsedOrder['vendor'],
                'status' => $parsedOrder['status'],
                'subject' => $emailData['subject'],
                'total_amount' => $parsedOrder['totalAmount'] ?? null,
                'order_date' => $parsedOrder['orderDate'] ?? null,
                'delivery_date' => $parsedOrder['deliveryDate'] ?? null,
                'items' => $parsedOrder['items'] ?? null,
                'metadata' => [
                    'replyTo' => $emailData['replyTo'],
                    'category' => $parsedOrder['category'] ?? null,
                    'deeplink' => $parsedOrder['deeplink'] ?? null,
                    'otp' => $parsedOrder['otp'] ?? null,
                ],
            ]);

            // 5. Update sync progress
            $this->updateSyncProgress(true);

        } catch (\Exception $e) {
            Log::error('ParseEmailJob failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Retry
        }
    }

    private function fetchEmailFromGmail(): array
    {
        $client = new Google_Client();
        $client->setAccessToken([
            'access_token' => $this->account->access_token, // Already decrypted by model
        ]);

        $service = new Google_Service_Gmail($client);
        $message = $service->users_messages->get('me', $this->messageId, [
            'format' => 'full',
        ]);

        $headers = collect($message->getPayload()->getHeaders());
        $from = $headers->firstWhere('name', 'From')['value'] ?? '';
        $subject = $headers->firstWhere('name', 'Subject')['value'] ?? '';
        $replyTo = $headers->firstWhere('name', 'Reply-To')['value'] ?? $from;

        $body = $this->extractBody($message->getPayload());
        $htmlBody = $this->extractHtmlBody($message->getPayload());

        return compact('from', 'subject', 'body', 'htmlBody', 'replyTo');
    }

    private function extractBody($payload): string
    {
        $body = '';
        
        if ($payload->getBody() && $payload->getBody()->getData()) {
            $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
        } elseif ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain') {
                    if ($part->getBody() && $part->getBody()->getData()) {
                        $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                        break;
                    }
                }
            }
        }
        
        return $body;
    }

    private function extractHtmlBody($payload): ?string
    {
        $htmlBody = null;
        
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/html') {
                    if ($part->getBody() && $part->getBody()->getData()) {
                        $htmlBody = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                        break;
                    }
                }
            }
        }
        
        return $htmlBody;
    }

    private function updateSyncProgress(bool $isOrder)
    {
        $syncJob = GmailSyncJob::find($this->syncJobId);
        if (!$syncJob) {
            return;
        }

        $syncJob->incrementProcessed($isOrder);

        // All emails processed?
        if ($syncJob->isComplete()) {
            // Update lastSyncedAt on account
            $this->account->update([
                'last_synced_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        $syncJob = GmailSyncJob::find($this->syncJobId);
        if ($syncJob) {
            $syncJob->markAsFailed($exception->getMessage());
        }

        Log::error('ParseEmailJob permanently failed', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
