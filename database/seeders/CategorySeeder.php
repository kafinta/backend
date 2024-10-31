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
            'image' => Storage::url('/images/categories/furniture.jpg')
        ]);
        Category::create([
            'name' => 'Lighting',
            'image' => Storage::url('/images/categories/lighting.jpg')
        ]);
        Category::create([
            'name' => 'Décor',
            'image' => Storage::url('/images/categories/decor.jpg')
        ]);
        Category::create([
            'name' => 'Entertainment',
            'image' => Storage::url('/images/categories/entertainment.jpg')

        ]);
        Category::create([
            'name' => 'Fabrics',
            'image' => Storage::url('/images/categories/fabrics.jpg')
        ]);
        Category::create([
            'name' => 'Appliances',
            'image' => Storage::url('/images/categories/appliances.jpg')
        ]);
        Category::create([
            'name' => 'Kitchenware',
            'image' => Storage::url('/images/categories/kitchenware.jpg')
        ]);
        Category::create([
            'name' => 'Plumbing Fixtures',
            'image' => Storage::url('/images/categories/plumbing.jpeg'),
        ]);
        Category::create([
            'name' => 'Storage solutions',
            'image' => Storage::url('/images/categories/storage.png')
        ]);
        Category::create([
            'name' => 'Outdoor & Gardening',
            'image' => Storage::url('/images/categories/gardening.jpg')
        ]);
        Category::create([
            'name' => 'Hardware & Tools',
            'image' => Storage::url('/images/categories/hardware.jpg')
        ]);
        Category::create([
            'name' => 'Home Improvement',
            'image' => Storage::url('/images/categories/improvement.jpeg')
        ]);
        Category::create([
            'name' => 'Personal Care & Hygiene',
            'image' => Storage::url('/images/categories/hygiene.jpg')
        ]);
    }
}
