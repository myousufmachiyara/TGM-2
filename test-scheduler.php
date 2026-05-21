<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "<h2>Testing Laravel Scheduler</h2>";
echo "<pre>";

echo "Running scheduler...\n";
echo "===================\n\n";

// Run the scheduler
Artisan::call('schedule:run');
echo Artisan::output();

echo "\n===================\n";
echo "Scheduler test complete!\n\n";

// Check if jobs were processed
$pendingJobs = DB::table('jobs')->count();
echo "Remaining jobs in queue: {$pendingJobs}\n";

// Check latest sync log
$latestLog = DB::table('shopify_sync_logs')->latest('id')->first();
if ($latestLog) {
    echo "\n--- Latest Sync Log ---\n";
    echo "Status: {$latestLog->status}\n";
    echo "Total: {$latestLog->total_products}\n";
    echo "Synced: {$latestLog->synced_products}\n";
    echo "Failed: {$latestLog->failed_products}\n";
    if ($latestLog->error_message) {
        echo "Error: {$latestLog->error_message}\n";
    }
}

echo "</pre>";
?>