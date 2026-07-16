<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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

        // Ensure fnb_images directory exists
        Storage::disk('public')->makeDirectory('fnb_images');

        $categoryColors = [
            'Minuman Dingin' => ['bg' => '#e0f2fe', 'fg' => '#0284c7', 'icon' => "\u{1F9CA}"],
            'Minuman Panas'  => ['bg' => '#fef3c7', 'fg' => '#d97706', 'icon' => "\u{2615}"],
            'Snack'          => ['bg' => '#fce7f3', 'fg' => '#db2777', 'icon' => "\u{1F35F}"],
            'Makanan'        => ['bg' => '#dcfce7', 'fg' => '#16a34a', 'icon' => "\u{1F35A}"],
        ];

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
            $colors = $categoryColors[$item['category']] ?? $categoryColors['Makanan'];
            $initials = mb_strimwidth($item['name'], 0, 2);
            $filename = 'fnb_images/' . \Illuminate\Support\Str::slug($item['name']) . '.svg';

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
                . '<rect width="200" height="200" rx="20" fill="' . $colors['bg'] . '"/>'
                . '<text x="100" y="90" text-anchor="middle" font-size="64">' . $colors['icon'] . '</text>'
                . '<text x="100" y="145" text-anchor="middle" font-family="sans-serif" font-size="16" font-weight="600" fill="' . $colors['fg'] . '">' . htmlspecialchars($item['name']) . '</text>'
                . '</svg>';

            Storage::disk('public')->put($filename, $svg);

            \App\Models\FnbItem::create(array_merge($item, ['image_path' => $filename]));
        }
    }
}
