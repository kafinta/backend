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
            'name' => 'Décor',
            'description' => 'Wall art, rugs, mirrors, etc...'
        ]);
        Category::create([
            'name' => 'Entertainment',
            'description' => 'TVs, computers, speakers, etc...'
        ]);
        Category::create([
            'name' => 'Linens',
            'description' => 'Bedding, curtains, towels, etc...'
        ]);
        Category::create([
            'name' => 'Appliances',
            'description' => 'Refrigerators, dishwashers, etc...'
        ]);
        Category::create([
            'name' => 'Kitchenware',
            'description' => 'Utensils, cookware, dining sets, etc...'
        ]);
        Category::create([
            'name' => 'Home office',
            'description' => 'Chairs, computers, printers, etc...'
        ]);
        Category::create([
            'name' => 'Storage solutions',
            'description' => 'Shelves, cabinets, baskets, etc...'
        ]);
        Category::create([
            'name' => 'Outdoor & Gardening',
            'description' => 'Patio furniture, grills, garden tools, etc...'
        ]);
        Category::create([
            'name' => 'Tools & supplies',
            'description' => 'Hammers, screwdrivers, cleaning supplies, etc...'
        ]);
        Category::create([
            'name' => 'Personal Care & Hygiene',
            'description' => 'Toiletries, grooming tools, etc...'
        ]);
        Category::create([
            'name' => 'Miscellaneous',
            'description' => 'Sporting goods, pet supplies, etc...'
        ]);
    }
}
