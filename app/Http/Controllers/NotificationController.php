<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->formatNotification($notification));

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'notification' => $this->formatNotification($notification->fresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    private function formatNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'message' => $notification->data['message'] ?? 'Notification',
            'icon' => $notification->data['icon'] ?? 'bell',
            'link' => $notification->data['link'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
