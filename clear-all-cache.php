<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "<h2>Clearing All Cache</h2>";

Artisan::call('config:clear');
echo "✓ Config cache cleared<br>";

Artisan::call('cache:clear');
echo "✓ Application cache cleared<br>";

Artisan::call('route:clear');
echo "✓ Route cache cleared<br>";

Artisan::call('view:clear');
echo "✓ View cache cleared<br>";

Artisan::call('clear-compiled');
echo "✓ Compiled files cleared<br>";

echo "<br><strong>All cache cleared successfully!</strong>";
?>