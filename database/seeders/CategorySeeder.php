<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Furniture',
            'image_path' => '/storage/categories/furniture.jpg'
        ]);
        Category::create([
            'name' => 'Lighting',
            'image_path' => '/storage/categories/lighting.jpg'
        ]);
        Category::create([
            'name' => 'Décor',
            'image_path' => '/storage/categories/decor.jpg'
        ]);
        Category::create([
            'name' => 'Entertainment',
            'image_path' => '/storage/categories/entertainment.jpg'

        ]);
        Category::create([
            'name' => 'Fabrics',
            'image_path' => '/storage/categories/fabrics.jpg'
        ]);
        Category::create([
            'name' => 'Appliances',
            'image_path' => '/storage/categories/appliances.jpg'
        ]);
        Category::create([
            'name' => 'Fixtures',
            'image_path' => '/storage/categories/plumbing.jpeg',
        ]);
        Category::create([
            'name' => 'Storage solutions',
            'image_path' => '/storage/categories/storage.png'
        ]);
        Category::create([
            'name' => 'Outdoor & Gardening',
            'image_path' => '/storage/categories/gardening.jpg'
        ]);
        Category::create([
            'name' => 'Hardware & Tools',
            'image_path' => '/storage/categories/hardware.jpg'
        ]);
        Category::create([
            'name' => 'Home Improvement',
            'image_path' => '/storage/categories/improvement.jpeg'
        ]);
        Category::create([
            'name' => 'Personal Care & Hygiene',
            'image_path' => '/storage/categories/hygiene.jpg'
        ]);
    }
}
