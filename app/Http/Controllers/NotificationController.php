<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\NotificationResource;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends ImprovedController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user notifications
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'sometimes|integer|min:1|max:100',
                'unread_only' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $perPage = $request->input('per_page', 20);
            $unreadOnly = $request->boolean('unread_only', false);

            $notifications = $this->notificationService->getUserNotifications(
                Auth::id(),
                $perPage,
                $unreadOnly
            );

            return $this->respondWithSuccess(
                'Notifications retrieved successfully',
                200,
                [
                    'notifications' => NotificationResource::collection($notifications->items()),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'has_more_pages' => $notifications->hasMorePages(),
                    ],
                    'unread_count' => $this->notificationService->getUnreadCount(Auth::id()),
                ]
            );
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get unread notification count
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        try {
            $count = $this->notificationService->getUnreadCount(Auth::id());

            return $this->respondWithSuccess('Unread count retrieved successfully', 200, [
                'unread_count' => $count
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving unread count: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        try {
            $success = $this->notificationService->markAsRead($id, Auth::id());

            if (!$success) {
                return $this->respondWithError('Notification not found', 404);
            }

            return $this->respondWithSuccess('Notification marked as read', 200, [
                'unread_count' => $this->notificationService->getUnreadCount(Auth::id())
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error marking notification as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        try {
            $count = $this->notificationService->markAllAsRead(Auth::id());

            return $this->respondWithSuccess('All notifications marked as read', 200, [
                'marked_count' => $count,
                'unread_count' => 0
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error marking all notifications as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete notification
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $success = $this->notificationService->deleteNotification($id, Auth::id());

            if (!$success) {
                return $this->respondWithError('Notification not found', 404);
            }

            return $this->respondWithSuccess('Notification deleted successfully', 200, [
                'unread_count' => $this->notificationService->getUnreadCount(Auth::id())
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error deleting notification: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get notification preferences
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreferences()
    {
        try {
            $preferences = NotificationPreference::getUserPreferences(Auth::id());

            return $this->respondWithSuccess('Notification preferences retrieved successfully', 200, [
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving preferences: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update notification preferences
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePreferences(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'preferences' => 'required|array',
                'preferences.*.email_enabled' => 'required|boolean',
                'preferences.*.app_enabled' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $success = NotificationPreference::updateUserPreferences(
                Auth::id(),
                $request->input('preferences')
            );

            if (!$success) {
                return $this->respondWithError('Failed to update preferences', 500);
            }

            $updatedPreferences = NotificationPreference::getUserPreferences(Auth::id());

            return $this->respondWithSuccess('Notification preferences updated successfully', 200, [
                'preferences' => $updatedPreferences
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error updating preferences: ' . $e->getMessage(), 500);
        }
    }
}
