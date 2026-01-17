<?php

namespace Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DBCore\Models\Core\KycDocumentType;

/**
 * KYC Document Type Controller
 *
 * Handles API requests for KYC document types (master data).
 *
 * @package Modules\Shared\Http\Controllers\Api\V1
 */
class KycDocumentTypeController extends Controller
{
    /**
     * Get all active KYC document types
     *
     * @operationId getKycDocumentTypes
     * @tags Master Data
     * @response 200 {"success": true, "data": [{"uuid": "...", "name": "PAN CARD", "description": "...", "is_active": true}]}
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $documentTypes = KycDocumentType::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documentTypes->map(function ($docType) {
                return [
                    'id' => $docType->id,
                    'uuid' => $docType->uuid,
                    'name' => $docType->name,
                    'description' => $docType->description,
                    'is_active' => $docType->is_active,
                ];
            }),
        ]);
    }
}

