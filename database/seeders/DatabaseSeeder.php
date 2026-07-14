<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin System',
            'username' => 'admin',
            'role' => 'admin',
            'password' => bcrypt('password')
        ]);

        User::factory()->create([
            'name' => 'Kasir 1',
            'username' => 'kasir',
            'role' => 'kasir',
            'password' => bcrypt('password')
        ]);

        \App\Models\Package::create(['name' => 'Regular Siang', 'price' => 25000]);
        \App\Models\Package::create(['name' => 'Regular Malam', 'price' => 35000]);
        \App\Models\Package::create(['name' => 'VIP', 'price' => 50000]);
        
        for($i = 1; $i <= 16; $i++) {
            \App\Models\Table::create(['name' => 'Meja ' . $i, 'relay_channel' => $i]);
        }
    }
}
