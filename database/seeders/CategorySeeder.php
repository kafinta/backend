<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Furniture',
            'description' => 'Chairs, tables, beds, etc...'
        ]);
        Category::create([
            'name' => 'Entertainment',
            'description' => 'TVs, audio players, etc...'
        ]);
        Category::create([
            'name' => 'Lighting',
            'description' => 'Bulbs, lamps, chandeliers, etc...'
        ]);
        Category::create([
            'name' => 'Decor',
            'description' => 'Wall art, rugs, mirrors, etc...'
        ]);
        Category::create([
            'name' => 'Fixtures',
            'description' => 'Taps, sinks, utensils, etc...'
        ]);
    }
}
