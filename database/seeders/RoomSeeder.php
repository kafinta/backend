<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Room::create([
            'name' => 'Living Room',
            'slug' => 'living',
        ]);
        Room::create([
            'name' => 'Bedroom',
            'slug' => 'bedroom',
        ]);
        Room::create([
            'name' => 'Kitchen',
            'slug' => 'kitchen',
        ]);
        Room::create([
            'name' => 'Dining',
            'slug' => 'dining',
        ]);
        Room::create([
            'name' => 'Home Office',
            'slug' => 'office',
        ]);
        Room::create([
            'name' => 'Kids Room',
            'slug' => 'kids',
        ]);
        Room::create([
            'name' => 'Storage & Closet',
            'slug' => 'closet',
        ]);
        Room::create([
            'name' => 'Home Bar',
            'slug' => 'bar',
        ]);
        Room::create([
            'name' => 'Home Gym',
            'slug' => 'gym',
        ]);
        Room::create([
            'name' => 'Wine Cellar',
            'slug' => 'cellar',
        ]);
        Room::create([
            'name' => 'Garage and shed',
            'slug' => 'garage',
        ]);
        Room::create([
            'name' => 'Bathroom',
            'slug' => 'bath',
        ]);
        Room::create([
            'name' => 'Toilet',
            'slug' => 'toilet',
        ]);
        Room::create([
            'name' => 'Outdoors',
            'slug' => 'outdoor',
        ]);
    }
}