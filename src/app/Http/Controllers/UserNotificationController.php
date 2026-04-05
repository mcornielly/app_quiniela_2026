<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = now()->subDay();
        $visibleNotificationsQuery = $user->notifications()
            ->when(
                Schema::hasColumn('notifications', 'hidden_at'),
                fn ($query) => $query->whereNull('hidden_at')
            );

        $notifications = (clone $visibleNotificationsQuery)
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(50)
            ->get();

        $items = $notifications
            ->filter(function ($notification) {
                $data = $notification->data ?? [];

                return ($data['status'] ?? null) === 'finished'
                    || in_array(($data['type'] ?? null), ['result', 'qualification'], true);
            })
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
            'unread' => $items->where('read', false)->count(),
            'total' => $items->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $query = $request->user()
            ->notifications()
            ->whereNull('read_at');

        if (Schema::hasColumn('notifications', 'hidden_at')) {
            $query->whereNull('hidden_at');
        }

        $query->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
        ]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $query = $request->user()
            ->notifications()
            ->where('id', $notificationId);

        if (Schema::hasColumn('notifications', 'hidden_at')) {
            $query->whereNull('hidden_at');
        }

        $notification = $query->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $query = $request->user()
            ->notifications()
            ->where('id', $notificationId);

        if (Schema::hasColumn('notifications', 'hidden_at')) {
            $query->whereNull('hidden_at')->update([
                'hidden_at' => now(),
                'read_at' => now(),
            ]);
        } else {
            $query->delete();
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if (Schema::hasColumn('notifications', 'hidden_at')) {
            $query->whereNull('hidden_at')->update([
                'hidden_at' => now(),
                'read_at' => now(),
            ]);
        } else {
            $query->delete();
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
