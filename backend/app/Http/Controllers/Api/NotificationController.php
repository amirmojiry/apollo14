<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class NotificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/notifications/subscribe",
     *     tags={"Notifications"},
     *     summary="Subscribe to push notifications",
     *     description="Subscribe to push notifications with device endpoint and keys",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"endpoint", "p256dh_key", "auth_key"},
     *             @OA\Property(property="endpoint", type="string", example="https://fcm.googleapis.com/fcm/send/..."),
     *             @OA\Property(property="p256dh_key", type="string", example="BEl62iUYgUivxIkv69yViEuiBIa40HI..."),
     *             @OA\Property(property="auth_key", type="string", example="tBHItJI5svbpez7KI4CCXg=="),
     *             @OA\Property(property="settings", type="object", example={"air_quality_alerts": true, "daily_reports": false})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully subscribed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully subscribed to notifications"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/notifications/unsubscribe",
     *     tags={"Notifications"},
     *     summary="Unsubscribe from push notifications",
     *     description="Unsubscribe from all push notifications",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully unsubscribed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully unsubscribed from notifications")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/notifications/settings",
     *     tags={"Notifications"},
     *     summary="Get notification settings",
     *     description="Retrieve user's notification settings and active subscriptions",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subscriptions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="settings", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/notifications/settings",
     *     tags={"Notifications"},
     *     summary="Update notification settings",
     *     description="Update user's notification preferences",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"notification_settings"},
     *             @OA\Property(
     *                 property="notification_settings",
     *                 type="object",
     *                 example={"air_quality_alerts": true, "daily_reports": false, "weekly_summary": true}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification settings updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
