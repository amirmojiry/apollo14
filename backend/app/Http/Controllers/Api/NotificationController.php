<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'p256dh_key' => 'required|string',
            'auth_key' => 'required|string',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Create or update subscription
        $subscription = NotificationSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh_key' => $request->p256dh_key,
                'auth_key' => $request->auth_key,
                'settings' => $request->settings ?? [],
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to notifications',
            'data' => $subscription
        ]);
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        NotificationSubscription::where('user_id', $user->id)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed from notifications'
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = NotificationSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'settings' => $user->notification_settings ?? [],
            ]
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'notification_settings' => $request->notification_settings
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
            'data' => $user->notification_settings
        ]);
    }
}
