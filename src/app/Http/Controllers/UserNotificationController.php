<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = now()->subDay();

        $user->notifications()
            ->where('created_at', '<', $since)
            ->delete();

        $notifications = $user->notifications()
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(50)
            ->get();

        $items = $notifications
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'update',
                    'gameId' => $data['gameId'] ?? null,
                    'tournamentId' => $data['tournamentId'] ?? null,
                    'stage' => $data['stage'] ?? null,
                    'stageLabel' => $data['stageLabel'] ?? 'Mundial 2026',
                    'status' => $data['status'] ?? null,
                    'homeTeam' => $data['homeTeam'] ?? 'Local',
                    'awayTeam' => $data['awayTeam'] ?? 'Visitante',
                    'homeCode' => $data['homeCode'] ?? null,
                    'awayCode' => $data['awayCode'] ?? null,
                    'homeFlagUrl' => $data['homeFlagUrl'] ?? null,
                    'awayFlagUrl' => $data['awayFlagUrl'] ?? null,
                    'homeScore' => $data['homeScore'] ?? 0,
                    'awayScore' => $data['awayScore'] ?? 0,
                    'matchDate' => $data['matchDate'] ?? null,
                    'matchTime' => $data['matchTime'] ?? null,
                    'venue' => $data['venue'] ?? null,
                    'message' => $data['message'] ?? null,
                    'occurredAt' => $data['occurredAt'] ?? optional($notification->created_at)->toIso8601String(),
                    'read' => !is_null($notification->read_at),
                ];
            })
            ->values();

        return response()->json([
            'notifications' => $items,
            'unread' => $notifications->whereNull('read_at')->count(),
            'total' => $notifications->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

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

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->delete();

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
