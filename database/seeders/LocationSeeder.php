<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create(['name' => 'Living Room']);
        Location::create(['name' => 'Bedroom']);
        Location::create(['name' => 'Kitchen']);
        Location::create(['name' => 'Dining']);
        Location::create(['name' => 'Bathroom']);
        Location::create(['name' => 'Hallway']);
        Location::create(['name' => 'Entryway']);
        Location::create(['name' => 'Closet']);
        Location::create(['name' => 'Home Office']);
        Location::create(['name' => 'Nursery']);
        Location::create(['name' => 'Guest Room']);
        Location::create(['name' => 'Garage & Shed']);
        Location::create(['name' => 'Porch']);
        Location::create(['name' => 'Deck']);
        Location::create(['name' => 'Pool Area']);
        Location::create(['name' => 'Patio']);
        Location::create(['name' => 'Basement']);
        Location::create(['name' => 'Attic']);
        Location::create(['name' => 'Laundry Room']);
        Location::create(['name' => 'Play Room']);
        Location::create(['name' => 'Pantry']);
        Location::create(['name' => 'Utility Room']);
        Location::create(['name' => 'Home theater']);
        Location::create(['name' => 'Home Gym']);
        Location::create(['name' => 'Driveway & Walkway']);
        Location::create(['name' => 'Playground']);
        Location::create(['name' => 'Exterior']);
        Location::create(['name' => 'Outdoor']);
    }
}
