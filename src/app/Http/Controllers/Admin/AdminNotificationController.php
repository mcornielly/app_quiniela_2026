<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                $registrationNumber = $data['registrationNumber'] ?? $data['poolEntryReference'] ?? null;

                return [
                    'id' => $notification->id,
                    'action' => $data['action'] ?? 'updated',
                    'userName' => $data['userName'] ?? 'Usuario',
                    'userEmail' => $data['userEmail'] ?? '',
                    'messageSuffix' => $data['messageSuffix'] ?? 'actualizo una quiniela.',
                    'poolEntryId' => $data['poolEntryId'] ?? null,
                    'poolEntryReference' => $data['poolEntryReference'] ?? null,
                    'registrationNumber' => $registrationNumber,
                    'poolEntryName' => $data['poolEntryName'] ?? null,
                    'occurredAt' => $data['occurredAt'] ?? optional($notification->created_at)->toIso8601String(),
                    'read' => !is_null($notification->read_at),
                ];
            })
            ->values();

        return response()->json([
            'notifications' => $items,
            'unread' => $user->unreadNotifications()->count(),
            'total' => $user->notifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
