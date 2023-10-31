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
            'slug' => 'stands'
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
            'slug' => 'entertainment'
        ]);
        Subcategory::create([
            'name' => 'Area Rugs',
            'room_id' => $livingRoom->id,
            'slug' => 'rugs'
        ]);
        Subcategory::create([
            'name' => 'Lamps & Lighting',
            'room_id' => $livingRoom->id,
            'slug' => 'lighting'
        ]);
        Subcategory::create([
            'name' => 'Decorative Accessories',
            'room_id' => $livingRoom->id,
            'slug' => 'decor'
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
            'name' => 'Makeup Vanities',
            'room_id' => $bedroom->id,
            'slug' => 'makeup'
        ]);
        Subcategory::create([
            'name' => 'Nightstands & Bedside Tables',
            'room_id' => $bedroom->id,
            'slug' => 'nightstands'
        ]);
        Subcategory::create([
            'name' => 'Dressers & Cabinets',
            'room_id' => $bedroom->id,
            'slug' => 'dressers'
        ]);
        Subcategory::create([
            'name' => 'Futons & Accessories',
            'room_id' => $bedroom->id,
            'slug' => 'futons'
        ]);
        Subcategory::create([
            'name' => 'Lamps & Lighting',
            'room_id' => $bedroom->id,
            'slug' => 'lamps'
        ]);
        Subcategory::create([
            'name' => 'Decorative Accessories',
            'room_id' => $bedroom->id,
            'slug' => 'bedrroom decor'
        ]);
        Subcategory::create([
            'name' => 'Dining tables',
            'room_id' => $dining->id,
            'slug' => 'tables'
        ]);
        Subcategory::create([
            'name' => 'Dining chairs',
            'room_id' => $dining->id,
            'slug' => 'chairs'
        ]);
        Subcategory::create([
            'name' => 'Dining sets',
            'room_id' => $dining->id,
            'slug' => 'sets'
        ]);
        Subcategory::create([
            'name' => 'Seat cushions',
            'room_id' => $dining->id,
            'slug' => 'seat cushions'
        ]);
        Subcategory::create([
            'name' => 'Dining lighting',
            'room_id' => $dining->id,
            'slug' => 'dining lighting'
        ]);
        Subcategory::create([
            'name' => 'Kitchen & table linens',
            'room_id' => $kitchen->id,
            'slug' => 'kitchen lighting'
        ]);
        Subcategory::create([
            'name' => 'Kitchen & table linens',
            'room_id' => $kitchen->id,
            'slug' => 'kitchen lighting'
        ]);
        Subcategory::create([
            'name' => 'Kitchen sinks',
            'room_id' => $kitchen->id,
            'slug' => 'sinks'
        ]);
        Subcategory::create([
            'name' => 'Kitchen faucets',
            'room_id' => $kitchen->id,
            'slug' => 'faucets'
        ]);
        Subcategory::create([
            'name' => 'Kitchen faucets',
            'room_id' => $kitchen->id,
            'slug' => 'faucets'
        ]);
        Subcategory::create([
            'name' => 'Pot fillers',
            'room_id' => $kitchen->id,
            'slug' => 'pot fillers'
        ]);
        Subcategory::create([
            'name' => 'Fixture parts',
            'room_id' => $kitchen->id,
            'slug' => 'fixtures'
        ]);
        Subcategory::create([
            'name' => 'Garbage disposals',
            'room_id' => $kitchen->id,
            'slug' => 'faucets'
        ]);
    }
}