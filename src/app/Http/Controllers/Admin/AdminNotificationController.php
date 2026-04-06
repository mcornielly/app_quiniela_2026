<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $supportsHiddenAt = Schema::hasColumn('notifications', 'hidden_at');
        $visibleNotificationsQuery = $user->notifications();

        if ($supportsHiddenAt) {
            $visibleNotificationsQuery->whereNull('hidden_at');
        }

        $items = (clone $visibleNotificationsQuery)
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                $registrationNumber = $data['registrationNumber'] ?? $data['poolEntryReference'] ?? null;

                return [
                    'id' => $notification->id,
                    'notificationKey' => $data['notificationKey'] ?? null,
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
            'unread' => (clone $visibleNotificationsQuery)->whereNull('read_at')->count(),
            'total' => (clone $visibleNotificationsQuery)->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $query = $request->user()->notifications()->whereNull('read_at');

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

    public function audit(Request $request): Response
    {
        $user = $request->user();

        $auditItems = $user->notifications()
            ->latest()
            ->limit(200)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                $registrationNumber = $data['registrationNumber'] ?? $data['poolEntryReference'] ?? null;

                return [
                    'id' => $notification->id,
                    'action' => $data['action'] ?? 'updated',
                    'userName' => $data['userName'] ?? 'Usuario',
                    'userEmail' => $data['userEmail'] ?? '',
                    'poolEntryName' => $data['poolEntryName'] ?? null,
                    'registrationNumber' => $registrationNumber,
                    'messageSuffix' => $data['messageSuffix'] ?? 'actualizo una quiniela.',
                    'createdAt' => optional($notification->created_at)->toIso8601String(),
                    'readAt' => optional($notification->read_at)->toIso8601String(),
                    'hiddenAt' => optional($notification->hidden_at)->toIso8601String(),
                ];
            })
            ->values();

        return Inertia::render('Admin/Notifications/Audit', [
            'auditItems' => $auditItems,
        ]);
    }
}
