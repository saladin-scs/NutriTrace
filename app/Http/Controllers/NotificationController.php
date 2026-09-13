<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $query = $user->notifications()->latest();

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $notifications = $query->limit(20)->get()->map(fn (DatabaseNotification $n) => $this->transform($n));

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'server_time' => now()->toIso8601String(),
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['ok' => true, 'unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? class_basename($notification->type),
            'level' => $data['level'] ?? 'info',
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? route('dashboard'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'is_unread' => $notification->read_at === null,
        ];
    }
}
