<?php

namespace Modules\GmailSync\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailSync\Models\GmailSyncJob;
use Modules\GmailSync\Services\GmailSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Gmail Sync Controller
 * 
 * Handles Gmail sync orchestration and status polling.
 */
class GmailSyncController extends Controller
{
    public function __construct(
        private GmailSyncService $syncService
    ) {
    }

    /**
     * Trigger Gmail sync
     * 
     * POST /api/gmail/accounts/{accountId}/sync
     */
    public function syncAccount(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = GmailAccount::where('id', $accountId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            // Start sync
            $syncJob = $this->syncService->startSync($account);

            if ($syncJob->total_emails === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No new emails to sync',
                    'data' => [
                        'total_emails' => 0,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync started',
                'data' => [
                    'job_id' => $syncJob->id,
                    'total_emails' => $syncJob->total_emails,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gmail account not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error starting Gmail sync', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start sync',
            ], 500);
        }
    }

    /**
     * Get sync status
     * 
     * GET /api/gmail/accounts/{accountId}/sync-status
     */
    public function getSyncStatus(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = GmailAccount::where('id', $accountId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            $syncJob = GmailSyncJob::where('gmail_account_id', $accountId)
                ->where('status', 'processing')
                ->latest()
                ->first();

            if (!$syncJob) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_complete' => true,
                        'processed' => 0,
                        'total' => 0,
                        'new_orders' => 0,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_complete' => $syncJob->isComplete(),
                    'processed' => $syncJob->processed_emails,
                    'total' => $syncJob->total_emails,
                    'new_orders' => $syncJob->new_orders,
                    'status' => $syncJob->status,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gmail account not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error getting sync status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get sync status',
            ], 500);
        }
    }
}
