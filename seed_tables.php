<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

for($i = 5; $i <= 16; $i++) {
    \App\Models\Table::firstOrCreate(['name' => 'Meja ' . $i], ['relay_channel' => $i]);
}
echo 'Meja 5 - 16 created.';
