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

        $fnbItems = [
            // Minuman Dingin
            ['name' => 'Es Teh Manis', 'category' => 'Minuman Dingin', 'price' => 8000],
            ['name' => 'Es Teh Tawar', 'category' => 'Minuman Dingin', 'price' => 5000],
            ['name' => 'Es Jeruk Segar', 'category' => 'Minuman Dingin', 'price' => 12000],
            ['name' => 'Es Lemon Tea', 'category' => 'Minuman Dingin', 'price' => 15000],
            ['name' => 'Es Milo', 'category' => 'Minuman Dingin', 'price' => 15000],
            ['name' => 'Es Good Day', 'category' => 'Minuman Dingin', 'price' => 12000],
            ['name' => 'Es Nutrisari', 'category' => 'Minuman Dingin', 'price' => 10000],
            ['name' => 'Air Mineral', 'category' => 'Minuman Dingin', 'price' => 5000],

            // Minuman Panas
            ['name' => 'Kopi Tubruk', 'category' => 'Minuman Panas', 'price' => 8000],
            ['name' => 'Kopi Susu', 'category' => 'Minuman Panas', 'price' => 15000],
            ['name' => 'Teh Panas', 'category' => 'Minuman Panas', 'price' => 5000],
            ['name' => 'Kapal Api', 'category' => 'Minuman Panas', 'price' => 8000],
            ['name' => 'Indocafe', 'category' => 'Minuman Panas', 'price' => 8000],

            // Snack
            ['name' => 'Kentang Goreng', 'category' => 'Snack', 'price' => 18000],
            ['name' => 'Pisang Goreng', 'category' => 'Snack', 'price' => 12000],
            ['name' => 'Roti Bakar', 'category' => 'Snack', 'price' => 15000],
            ['name' => 'French Fries', 'category' => 'Snack', 'price' => 20000],
            ['name' => 'Chicken Wings', 'category' => 'Snack', 'price' => 25000],
            ['name' => 'Nugget Goreng', 'category' => 'Snack', 'price' => 18000],

            // Makanan
            ['name' => 'Nasi Goreng', 'category' => 'Makanan', 'price' => 22000],
            ['name' => 'Mie Goreng', 'category' => 'Makanan', 'price' => 20000],
            ['name' => 'Mie Rebus', 'category' => 'Makanan', 'price' => 20000],
            ['name' => 'Nasi Ayam Goreng', 'category' => 'Makanan', 'price' => 25000],
            ['name' => 'Nasi Rendang', 'category' => 'Makanan', 'price' => 30000],
            ['name' => 'Gado-gado', 'category' => 'Makanan', 'price' => 18000],
            ['name' => 'Sate Ayam (10 tusuk)', 'category' => 'Makanan', 'price' => 25000],
            ['name' => 'Sosis Goreng', 'category' => 'Makanan', 'price' => 15000],
        ];

        foreach ($fnbItems as $item) {
            \App\Models\FnbItem::create($item);
        }
    }
}
