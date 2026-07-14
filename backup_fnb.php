<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$items = App\Models\FnbItem::all();
file_put_contents('fnb_backup.json', $items->toJson());
echo 'Backed up ' . $items->count() . ' items.';
