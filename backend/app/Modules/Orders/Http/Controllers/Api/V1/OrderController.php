<?php

namespace Modules\Orders\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Orders\Models\Order;
use Modules\Orders\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Dedoc\Scramble\Attributes\Response;
/**
 * Order Controller
 *
 * Handles order CRUD operations with advanced filtering and pagination.
 *
 * @tags Orders
 */
class OrderController extends Controller
{
    /**
     * List orders with filtering and pagination
     *
     * Query params: gmailAccountId, vendor, status, statuses[], category, categories[], domain, startDate, endDate, search, sortBy, sortOrder, perPage.
     *
     * @operationId ordersIndex
     * @tags Orders
     * @response 200 {"success": true, "data": [{"id": "550e8400-e29b-41d4-a716-446655440001", "userId": "550e8400-e29b-41d4-a716-446655440000", "gmailAccountId": "660e8400-e29b-41d4-a716-446655440001", "emailId": "18c1234567890abcdef", "orderId": "ORD-12345", "vendor": "Amazon", "status": "confirmed", "subject": "Your order #ORD-12345 has been confirmed", "totalAmount": 129.99, "orderDate": "2024-01-15T10:30:00Z", "deliveryDate": "2024-01-20T14:00:00Z", "items": [{"name": "Product 1", "quantity": 2, "price": 64.99}], "metadata": {"trackingNumber": "TRACK123"}, "createdAt": "2024-01-15T10:35:00Z", "updatedAt": "2024-01-15T10:35:00Z"}], "meta": {"currentPage": 1, "perPage": 20, "total": 100, "lastPage": 5, "hasMore": true}}
     * @response 500 {"success": false, "error": "Failed to list orders"}
     */
    #[Response(200, 'Orders listed successfully', type: 'array{success: bool, data: array{id: string, userId: string, gmailAccountId: string, emailId: string, orderId: string, vendor: string, status: string, subject: string, totalAmount: float, orderDate: string, deliveryDate: string, items: array{name: string, quantity: int, price: float}, metadata: array{trackingNumber: string}, createdAt: string, updatedAt: string}}')]
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
     * Body: gmailAccountId (optional), emailId, orderId, vendor, status, subject, totalAmount, orderDate, deliveryDate, items, metadata.
     *
     * @operationId ordersStore
     * @tags Orders
     * @response 201 {"success": true, "message": "Order created successfully", "data": {"id": "550e8400-e29b-41d4-a716-446655440001", "userId": "550e8400-e29b-41d4-a716-446655440000", "vendor": "Amazon", "status": "pending", "subject": "Your order has been placed", "totalAmount": 99.99, "orderDate": "2024-01-15T10:30:00Z", "createdAt": "2024-01-15T10:35:00Z", "updatedAt": "2024-01-15T10:35:00Z"}}
     * @response 409 {"success": false, "error": "Order already exists", "message": "An order with this email ID already exists"}
     * @response 422 {"success": false, "message": "Validation failed", "errors": {"vendor": ["The vendor field is required."], "status": ["The status field is required."]}}
     * @response 500 {"success": false, "error": "Failed to create order"}
     */
    #[Response(201, 'Order created successfully', type: 'array{success: bool, message: string, data: array{id: string, userId: string, vendor: string, status: string, subject: string, totalAmount: float, orderDate: string, createdAt: string, updatedAt: string}}')]
    #[Response(409, 'Order already exists', type: 'array{success: bool, error: string, message: string}')]
    #[Response(422, 'Validation failed', type: 'array{success: bool, message: string, errors: array{vendor: string[], status: string[]}}')]
    #[Response(500, 'Failed to create order', type: 'array{success: bool, error: string}')]
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
     * @operationId ordersShow
     * @tags Orders
     * @response 200 {"success": true, "data": {"id": "550e8400-e29b-41d4-a716-446655440001", "userId": "550e8400-e29b-41d4-a716-446655440000", "gmailAccountId": "660e8400-e29b-41d4-a716-446655440001", "emailId": "18c1234567890abcdef", "orderId": "ORD-12345", "vendor": "Amazon", "status": "confirmed", "subject": "Your order #ORD-12345 has been confirmed", "totalAmount": 129.99, "orderDate": "2024-01-15T10:30:00Z", "deliveryDate": "2024-01-20T14:00:00Z", "items": [{"name": "Product 1", "quantity": 2, "price": 64.99}], "metadata": {"trackingNumber": "TRACK123"}, "createdAt": "2024-01-15T10:35:00Z", "updatedAt": "2024-01-15T10:35:00Z"}}
     * @response 404 {"success": false, "error": "Order not found"}
     * @response 500 {"success": false, "error": "Failed to get order"}
     */
    #[Response(200, 'Order retrieved successfully', type: 'array{success: bool, data: array{id: string, userId: string, gmailAccountId: string, emailId: string, orderId: string, vendor: string, status: string, subject: string, totalAmount: float, orderDate: string, deliveryDate: string, items: array{name: string, quantity: int, price: float}, metadata: array{trackingNumber: string}, createdAt: string, updatedAt: string}}')]
    #[Response(404, 'Order not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to get order', type: 'array{success: bool, error: string}')]
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
     * Body: status (optional), deliveryDate (optional).
     *
     * @operationId ordersUpdate
     * @tags Orders
     * @response 200 {"success": true, "message": "Order updated successfully", "data": {"id": "550e8400-e29b-41d4-a716-446655440001", "status": "shipped", "deliveryDate": "2024-01-22T14:00:00Z", "updatedAt": "2024-01-16T09:00:00Z"}}
     * @response 404 {"success": false, "error": "Order not found"}
     * @response 422 {"success": false, "message": "Validation failed", "errors": {"deliveryDate": ["The delivery date must be a valid date."]}}
     * @response 500 {"success": false, "error": "Failed to update order"}
     */
    #[Response(200, 'Order updated successfully', type: 'array{success: bool, message: string, data: array{id: string, status: string, deliveryDate: string, updatedAt: string}}')]
    #[Response(404, 'Order not found', type: 'array{success: bool, error: string}')]
    #[Response(422, 'Validation failed', type: 'array{success: bool, message: string, errors: array{deliveryDate: string[]}}')]
    #[Response(500, 'Failed to update order', type: 'array{success: bool, error: string}')]
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
     * @operationId ordersDestroy
     * @tags Orders
     * @response 200 {"success": true, "message": "Order deleted successfully", "data": {"id": "550e8400-e29b-41d4-a716-446655440001"}}
     * @response 404 {"success": false, "error": "Order not found"}
     * @response 500 {"success": false, "error": "Failed to delete order"}
     */
    #[Response(200, 'Order deleted successfully', type: 'array{success: bool, message: string, data: array{id: string}}')]
    #[Response(404, 'Order not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to delete order', type: 'array{success: bool, error: string}')]
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
