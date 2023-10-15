<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subcategory;
use App\Models\Room;

class SubcategorySeeder extends Seeder
{
    public function run()
    {
        $livingRoom = Room::where('slug', 'living')->first();
        $bedroom = Room::where('slug', 'bedroom')->first();
        $kitchen = Room::where('slug', 'kitchen')->first();
        $dining = Room::where('slug', 'dining')->first();
        $office = Room::where('slug', 'office')->first();
        $kids = Room::where('slug', 'kids')->first();
        $closet = Room::where('slug', 'closet')->first();
        $homeBar = Room::where('slug', 'bar')->first();
        $homeGym = Room::where('slug', 'gym')->first();
        $cellar = Room::where('slug', 'cellar')->first();
        $garage = Room::where('slug', 'garage')->first();
        $bathroom = Room::where('slug', 'bath')->first();
        $toilet = Room::where('slug', 'toilet')->first();
        $outdoors = Room::where('slug', 'outdoors')->first();

        Subcategory::create([
            'name' => 'Sofas & Couches',
            'room_id' => $livingRoom->id,
            'slug' => 'sofas'
        ]);
        Subcategory::create([
            'name' => 'Recliners & Sectionals',
            'room_id' => $livingRoom->id,
            'slug' => 'recliners'
        ]);
        Subcategory::create([
            'name' => 'Coffee & Accent Tables',
            'room_id' => $livingRoom->id,
            'slug' => 'tables'
        ]);
        Subcategory::create([
            'name' => 'Entertainment Centers',
            'room_id' => $livingRoom->id,
            'slug' => 'entertainment'
        ]);
        Subcategory::create([
            'name' => 'Bookshelves & Storage Units',
            'room_id' => $livingRoom->id,
            'slug' => 'bookshelves'
        ]);
        Subcategory::create([
            'name' => 'Throw Pillows & Cushions',
            'room_id' => $livingRoom->id,
            'slug' => 'cushions'
        ]);
        Subcategory::create([
            'name' => 'Entertainment Systems',
            'room_id' => $livingRoom->id,
            'slug' => 'cushions'
        ]);
        Subcategory::create([
            'name' => 'Area Rugs',
            'room_id' => $livingRoom->id,
            'slug' => 'rugs'
        ]);
        Subcategory::create([
            'name' => 'Decorative Accessories',
            'room_id' => $livingRoom->id,
            'slug' => 'accessories'
        ]);
        Subcategory::create([
            'name' => 'Beds & Headboards',
            'room_id' => $bedroom->id,
            'slug' => 'beds'
        ]);
        Subcategory::create([
            'name' => 'Bedding',
            'room_id' => $bedroom->id,
            'slug' => 'bedding'
        ]);
        Subcategory::create([
            'name' => 'Mattresses',
            'room_id' => $bedroom->id,
            'slug' => 'mattresses'
        ]);
        Subcategory::create([
            'name' => 'Mattresses',
            'room_id' => $bedroom->id,
            'slug' => 'mattresses'
        ]);
        Subcategory::create([
            'name' => 'Mattresses',
            'room_id' => $bedroom->id,
            'slug' => 'mattresses'
        ]);
    }
}