<?php

namespace Modules\Orders\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Orders\Models\Order;
use Modules\Orders\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Order Controller
 * 
 * Handles order CRUD operations with advanced filtering.
 */
class OrderController extends Controller
{
    /**
     * List orders with filtering and pagination
     * 
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::where('user_id', $request->user()->uuid);

            // Filter by Gmail account
            if ($request->gmailAccountId) {
                $query->where('gmail_account_id', $request->gmailAccountId);
            }

            // Filter by vendor
            if ($request->vendor) {
                $query->vendor($request->vendor);
            }

            // Filter by single status
            if ($request->status) {
                $query->status($request->status);
            }

            // Filter by multiple statuses (array)
            if ($request->has('statuses') && is_array($request->statuses)) {
                $query->statuses($request->statuses);
            }

            // Filter by single category
            if ($request->category) {
                $query->category($request->category);
            }

            // Filter by multiple categories (array)
            if ($request->has('categories') && is_array($request->categories)) {
                $query->categories($request->categories);
            }

            // Filter by domain (replyTo)
            if ($request->domain) {
                $query->domain($request->domain);
            }

            // Filter by date range
            if ($request->startDate || $request->endDate) {
                $query->dateRange($request->startDate, $request->endDate);
            }

            // Search
            if ($request->search) {
                $query->search($request->search);
            }

            // Sort
            $sortBy = $request->sortBy ?? 'order_date';
            $sortOrder = $request->sortOrder ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = min($request->perPage ?? 20, 100);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => OrderResource::collection($orders),
                'meta' => [
                    'currentPage' => $orders->currentPage(),
                    'perPage' => $orders->perPage(),
                    'total' => $orders->total(),
                    'lastPage' => $orders->lastPage(),
                    'hasMore' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error listing orders', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list orders',
            ], 500);
        }
    }

    /**
     * Create order
     * 
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'gmailAccountId' => 'nullable|uuid|exists:gmail_accounts,id',
            'emailId' => 'nullable|string|max:255',
            'orderId' => 'nullable|string|max:255',
            'vendor' => 'required|string|max:100',
            'status' => 'required|string|max:50',
            'subject' => 'required|string',
            'totalAmount' => 'nullable|numeric',
            'orderDate' => 'nullable|date',
            'deliveryDate' => 'nullable|date',
            'items' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            // Check for duplicate (email_id + user_id must be unique)
            if ($request->emailId) {
                $existing = Order::where('email_id', $request->emailId)
                    ->where('user_id', $request->user()->uuid)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Order already exists',
                        'message' => 'An order with this email ID already exists',
                    ], 409);
                }
            }

            $order = Order::create([
                'user_id' => $request->user()->uuid,
                'gmail_account_id' => $request->gmailAccountId,
                'email_id' => $request->emailId,
                'order_id' => $request->orderId,
                'vendor' => $request->vendor,
                'status' => $request->status,
                'subject' => $request->subject,
                'total_amount' => $request->totalAmount,
                'order_date' => $request->orderDate,
                'delivery_date' => $request->deliveryDate,
                'items' => $request->items,
                'metadata' => $request->metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating order', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create order',
            ], 500);
        }
    }

    /**
     * Get single order
     * 
     * GET /api/orders/{orderId}
     */
    public function show(Request $request, string $orderId): JsonResponse
    {
        try {
            $order = Order::where('id', $orderId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new OrderResource($order),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error getting order', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get order',
            ], 500);
        }
    }

    /**
     * Update order
     * 
     * PUT /api/orders/{orderId}
     */
    public function update(Request $request, string $orderId): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|string|max:50',
            'deliveryDate' => 'nullable|date',
        ]);

        try {
            $order = Order::where('id', $orderId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            $updateData = [];
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('deliveryDate')) {
                $updateData['delivery_date'] = $request->deliveryDate;
            }

            $order->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating order', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update order',
            ], 500);
        }
    }

    /**
     * Delete order
     * 
     * DELETE /api/orders/{orderId}
     */
    public function destroy(Request $request, string $orderId): JsonResponse
    {
        try {
            $order = Order::where('id', $orderId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
                'data' => [
                    'id' => $orderId,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting order', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete order',
            ], 500);
        }
    }
}
