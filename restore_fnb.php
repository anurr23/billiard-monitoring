<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$json = file_get_contents('fnb_backup.json');
$items = json_decode($json, true);
foreach ($items as $item) {
    App\Models\FnbItem::create([
        'category' => $item['category'],
        'name' => $item['name'],
        'price' => $item['price'],
        'image_path' => $item['image_path'] ?? null,
    ]);
}
echo 'Restored ' . count($items) . ' items.';
