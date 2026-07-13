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
            'name' => 'Admin Kasir',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password')
        ]);

        \App\Models\Package::create(['name' => 'Regular Siang', 'price' => 25000]);
        \App\Models\Package::create(['name' => 'Regular Malam', 'price' => 35000]);
        \App\Models\Package::create(['name' => 'VIP', 'price' => 50000]);
        
        \App\Models\Table::create(['name' => 'Meja 1', 'relay_channel' => 1]);
        \App\Models\Table::create(['name' => 'Meja 2', 'relay_channel' => 2]);
        \App\Models\Table::create(['name' => 'Meja 3', 'relay_channel' => 3]);
        \App\Models\Table::create(['name' => 'Meja 4', 'relay_channel' => 4]);
    }
}
