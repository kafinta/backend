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
            'description' => 'Chairs, tables, beds, etc...',
            'image' => Storage::url('/images/categories/furniture.jpg')
        ]);
        Category::create([
            'name' => 'Lighting',
            'description' => 'Lamps, bulbs, etc...',
            'image' => Storage::url('/images/categories/lighting.jpg')
        ]);
        Category::create([
            'name' => 'Décor',
            'description' => 'Wall art, rugs, mirrors, etc...',
            'image' => Storage::url('/images/categories/decor.jpg')
        ]);
        Category::create([
            'name' => 'Entertainment',
            'description' => 'TVs, computers, speakers, etc...',
            'image' => Storage::url('/images/categories/entertainment.jpg')

        ]);
        Category::create([
            'name' => 'Fabrics',
            'description' => 'Bedding, curtains, towels, etc...',
            'image' => Storage::url('/images/categories/fabrics.jpg')
        ]);
        Category::create([
            'name' => 'Appliances',
            'description' => 'Refrigerators, dishwashers, etc...',
            'image' => Storage::url('/images/categories/appliances.jpg')
        ]);
        Category::create([
            'name' => 'Kitchenware',
            'description' => 'Utensils, cookware, dining sets, etc...',
            'image' => Storage::url('/images/categories/kitchenware.jpg')
        ]);
        Category::create([
            'name' => 'Plumbing Fixtures',
            'description' => 'Sinks, faucets, bathtubs, etc...',
            'image' => Storage::url('/images/categories/plumbing.jpeg'),
        ]);
        Category::create([
            'name' => 'Home office',
            'description' => 'Chairs, computers, printers, etc...',
            'image' => Storage::url('/images/categories/office.jpg')
        ]);
        Category::create([
            'name' => 'Storage solutions',
            'description' => 'Shelves, cabinets, baskets, etc...',
            'image' => Storage::url('/images/categories/storage.png')
        ]);
        Category::create([
            'name' => 'Outdoor & Gardening',
            'description' => 'Patio furniture, grills, garden tools, etc...',
            'image' => Storage::url('/images/categories/gardening.jpg')
        ]);
        Category::create([
            'name' => 'Hardware & Tools',
            'description' => 'Hammers, screwdrivers, cleaning supplies, etc...',
            'image' => Storage::url('/images/categories/hardware.jpg')
        ]);
        Category::create([
            'name' => 'Home Improvement',
            'description' => 'Doors, blinds, tiles, etc...',
            'image' => Storage::url('/images/categories/improvement.jpeg')
        ]);
        Category::create([
            'name' => 'Personal Care & Hygiene',
            'description' => 'Toiletries, grooming tools, etc...',
            'image' => Storage::url('/images/categories/hygiene.jpg')
        ]);
    }
}
