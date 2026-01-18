<?php

namespace Modules\GmailSync\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailSync\Models\GmailSyncJob;
use Modules\GmailSync\Services\GmailSyncService;
use Illuminate\Support\Facades\Log;
use Dedoc\Scramble\Attributes\Response;
/**
 * Gmail Sync Controller
 *
 * Handles Gmail sync orchestration and status polling.
 *
 * @tags Gmail
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
     * Starts syncing emails from the Gmail account and parsing orders. Returns job_id for status polling.
     *
     * @operationId gmailSyncStart
     * @tags Gmail
     * @response 200 {"success": true, "message": "Sync started", "data": {"job_id": "770e8400-e29b-41d4-a716-446655440001", "total_emails": 42}}
     * @response 200 {"success": true, "message": "No new emails to sync", "data": {"total_emails": 0}}
     * @response 404 {"success": false, "error": "Gmail account not found"}
     * @response 500 {"success": false, "error": "Failed to start sync"}
     */
    #[Response(200, 'Sync started', type: 'array{success: bool, message: string, data: array{job_id: string, total_emails: int}}')]
    #[Response(200, 'No new emails to sync', type: 'array{success: bool, message: string, data: array{total_emails: int}}')]
    #[Response(404, 'Gmail account not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to start sync', type: 'array{success: bool, error: string}')]
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
     * Returns the status of the latest in-progress or completed sync for the Gmail account. Use for polling.
     *
     * @operationId gmailSyncStatus
     * @tags Gmail
     * @response 200 {"success": true, "data": {"is_complete": true, "processed": 10, "total": 10, "new_orders": 3, "status": "completed"}}
     * @response 200 {"success": true, "data": {"is_complete": true, "processed": 0, "total": 0, "new_orders": 0, "status": null}}
     * @response 404 {"success": false, "error": "Gmail account not found"}
     * @response 500 {"success": false, "error": "Failed to get sync status"}
     */
    #[Response(200, 'Sync status retrieved successfully', type: 'array{success: bool, data: array{is_complete: bool, processed: int, total: int, new_orders: int, status: string}}')]
    #[Response(200, 'No sync status', type: 'array{success: bool, data: array{is_complete: bool, processed: int, total: int, new_orders: int, status: null}}')]
    #[Response(404, 'Gmail account not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to get sync status', type: 'array{success: bool, error: string}')]
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
