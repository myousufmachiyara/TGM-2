<?php
require __DIR__.'/vendor/autoload.php';

echo "<h2>Kernel File Verification</h2>";

$kernelPath = __DIR__ . '/app/Console/Kernel.php';
$commandsPath = __DIR__ . '/app/Console/Commands';
$consolePath = __DIR__ . '/routes/console.php';

echo "<strong>Kernel.php:</strong> ";
if (file_exists($kernelPath)) {
    echo "✓ EXISTS<br>";
} else {
    echo "✗ NOT FOUND<br>";
    echo "Expected at: {$kernelPath}<br>";
}

echo "<strong>Commands directory:</strong> ";
if (is_dir($commandsPath)) {
    echo "✓ EXISTS<br>";
} else {
    echo "✗ NOT FOUND<br>";
    echo "Expected at: {$commandsPath}<br>";
}

echo "<strong>routes/console.php:</strong> ";
if (file_exists($consolePath)) {
    echo "✓ EXISTS<br>";
} else {
    echo "✗ NOT FOUND<br>";
    echo "Expected at: {$consolePath}<br>";
}

echo "<br><h3>Testing Scheduler:</h3>";

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
    $kernel->bootstrap();
    
    echo "✓ Kernel loaded successfully!<br><br>";
    
    echo "Running schedule:run...<br>";
    Artisan::call('schedule:run');
    echo "<pre>" . Artisan::output() . "</pre>";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
