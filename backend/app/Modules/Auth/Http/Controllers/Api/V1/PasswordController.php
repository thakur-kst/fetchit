<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\ChangePasswordRequest;
use Modules\Auth\Services\PasswordService;

/**
 * Password Controller
 *
 * Handles password change for authenticated users.
 * Note: FetchIt primarily uses Google OAuth, but supports password changes for users with local passwords.
 *
 * @tags Auth
 */
class PasswordController extends Controller
{
    public function __construct(
        private PasswordService $passwordService
    ) {
    }

    /**
     * Change password
     *
     * Allows authenticated users to change their password.
     * Requires current password verification and updates password in local database.
     *
     * @operationId changePassword
     * @tags Auth
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     * @response 200 {"success": true, "message": "Password changed successfully", "data": {"email": "user@example.com"}}
     * @response 400 {"success": false, "message": "Current password is incorrect"}
     * @response 401 {"success": false, "message": "User not authenticated"}
     * @response 422 {"success": false, "message": "Validation failed", "errors": {"current_password": ["The current password field is required."]}}
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $result = $this->passwordService->changePassword(
                $user->id,
                $validated['current_password'],
                $validated['new_password']
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
