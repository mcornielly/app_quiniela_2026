<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::query()->where('is_admin', true)->first();
$user = App\Models\User::query()->where('is_admin', false)->first() ?: $admin;
$entry = App\Models\PoolEntry::query()->first();

if (!$admin || !$entry) {
    echo "missing admin or pool_entry\n";
    exit(1);
}

$admin->notify(new App\Notifications\AdminPoolActivityNotification($entry, $user, 'reactivated'));

$count = Illuminate\Support\Facades\DB::table('notifications')->count();
$latest = Illuminate\Support\Facades\DB::table('notifications')->orderByDesc('created_at')->first();

echo "count={$count}\n";
echo "latest_notifiable_id=" . ($latest->notifiable_id ?? 'null') . "\n";
