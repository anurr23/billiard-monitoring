<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = \App\Models\User::where('username', 'admin')->first();
if ($admin) {
    $admin->role = 'admin';
    $admin->save();
}

\App\Models\User::firstOrCreate(
    ['username' => 'kasir'],
    ['name' => 'Kasir 1', 'role' => 'kasir', 'password' => bcrypt('password')]
);

echo 'Users setup.';
