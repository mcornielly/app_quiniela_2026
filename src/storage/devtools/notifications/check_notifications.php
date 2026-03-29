<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = Illuminate\Support\Facades\DB::table('notifications')->count();
$latest = Illuminate\Support\Facades\DB::table('notifications')->orderByDesc('created_at')->first();
$admins = Illuminate\Support\Facades\DB::table('users')->where('is_admin', 1)->pluck('id');

echo "admins=" . json_encode($admins) . PHP_EOL;
echo "count={$count}" . PHP_EOL;
echo "latest=" . json_encode($latest) . PHP_EOL;
