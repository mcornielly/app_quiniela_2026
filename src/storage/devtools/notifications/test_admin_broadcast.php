<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    event(new App\Events\AdminPoolActivityBroadcast([
        'action' => 'reactivated',
        'userName' => 'Debug',
        'messageSuffix' => 'debug',
        'occurredAt' => now()->toIso8601String(),
    ]));
    echo "broadcast_dispatched\n";
} catch (Throwable $e) {
    echo "broadcast_error: " . $e->getMessage() . "\n";
}
