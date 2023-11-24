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
        Location::create([
            'name' => 'Living Room',
            'image' => '/images/locations/living room.jpg'
        ]);
        Location::create([
            'name' => 'Bedroom',
            'image' => '/images/location/bedroom.jpg'

        ]);
        Location::create([
            'name' => 'Kitchen',
            'image' => '/images/locations/kitchen.jpg'
        ]);
        Location::create([
            'name' => 'Dining',
            'image' => '/images/locations/dining.jpg'
        ]);
        Location::create([
            'name' => 'Bathroom',
            'image' => '/images/locations/bathroom.jpg'
        ]);
        Location::create([
            'name' => 'Hallway',
            'image' => '/images/locations/hallway.jpg'
        ]);
        Location::create([
            'name' => 'Closet',
            'image' => '/images/locations/closet.jpg'
        ]);
        Location::create([
            'name' => 'Home Office',
            'image' => '/images/locations/home office.jpg'
        ]);
        Location::create([
            'name' => 'Kids Room',
            'image' => '/images/locations/nursery.jpg'
        ]);
        Location::create([
            'name' => 'Garage & Shed',
            'image' => '/images/locations/garage.jpg'
        ]);
        Location::create([
            'name' => 'Porch',
            'image' => '/images/locations/porch.jpg'
        ]);
        Location::create([
            'name' => 'Deck',
            'image' => '/images/locations/deck.jpeg'
        ]);
        Location::create([
            'name' => 'Pool Area',
            'image' => '/images/locations/pool area.jpg'
        ]);
        Location::create([
            'name' => 'Patio',
            'image' => '/images/locations/patio.png'
        ]);
        Location::create([
            'name' => 'Basement',
            'image' => '/images/locations/basement.jpg'
        ]);
        Location::create([
            'name' => 'Attic',
            'image' => '/images/locations/attic.jpg'
        ]);
        Location::create([
            'name' => 'Utility Room',
            'image' => '/images/locations/utility room.png'
        ]);
        Location::create([
            'name' => 'Home theater',
            'image' => '/images/locations/home theatre.jpg'
        ]);
        Location::create([
            'name' => 'Home Gym',
            'image' => '/images/locations/home gym.jpg'
        ]);
        Location::create([
            'name' => 'Driveway & Walkway',
            'image' => '/images/locations/driveway.jpg'
        ]);
        Location::create([
            'name' => 'Exterior',
            'image' => '/images/locations/exterior.jpg'
        ]);
        Location::create([
            'name' => 'Outdoor',
            'image' => '/images/locations/outdoor.jpg'
        ]);
    }
}
