<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order Resource
 * 
 * Formats order data for API responses.
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'gmailAccountId' => $this->gmail_account_id,
            'emailId' => $this->email_id,
            'orderId' => $this->order_id,
            'vendor' => $this->vendor,
            'status' => $this->status,
            'subject' => $this->subject,
            'totalAmount' => $this->total_amount ? (string) $this->total_amount : null,
            'orderDate' => $this->order_date?->toIso8601String(),
            'deliveryDate' => $this->delivery_date?->toIso8601String(),
            'items' => $this->items,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
