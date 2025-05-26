<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Living Room',
                'image_path' => '/storage/locations/living.jpg'
            ],
            [
                'name' => 'Bedroom',
                'image_path' => '/storage/locations/bedroom.jpg'
            ],
            [
                'name' => 'Kitchen',
                'image_path' => '/storage/locations/kitchen.jpg'
            ],
            [
                'name' => 'Dining Room',
                'image_path' => '/storage/locations/dining.jpg'
            ],
            [
                'name' => 'Bathroom',
                'image_path' => '/storage/locations/bathroom.jpg'
            ],
            [
                'name' => 'Entryway & Hallway',
                'image_path' => '/storage/locations/hallway.jpg'
            ],
            [
                'name' => 'Closet & Storage',
                'image_path' => '/storage/locations/closet.jpg'
            ],
            [
                'name' => 'Home Office',
                'image_path' => '/storage/locations/office.jpg'
            ],
            [
                'name' => 'Children\'s Room & Nursery',
                'image_path' => '/storage/locations/nursery.jpg'
            ],
            [
                'name' => 'Garage & Shed',
                'image_path' => '/storage/locations/garage.jpg'
            ],
            [
                'name' => 'Porch',
                'image_path' => '/storage/locations/porch.jpg'
            ],
            [
                'name' => 'Deck & Patio',
                'image_path' => '/storage/locations/deck.jpeg'
            ],
            [
                'name' => 'Pool Area',
                'image_path' => '/storage/locations/pool.jpg'
            ],
            [
                'name' => 'Basement',
                'image_path' => '/storage/locations/basement.jpg'
            ],
            [
                'name' => 'Attic',
                'image_path' => '/storage/locations/attic.jpg'
            ],
            [
                'name' => 'Game Room',
                'image_path' => '/storage/locations/game_room.webp'
            ],
            [
                'name' => 'Laundry Room',
                'image_path' => '/storage/locations/laundry.png'
            ],
            [
                'name' => 'Home theater',
                'image_path' => '/storage/locations/theatre.jpg'
            ],
            [
                'name' => 'Home Gym',
                'image_path' => '/storage/locations/gym.jpg'
            ],
            [
                'name' => 'Driveway & Walkway',
                'image_path' => '/storage/locations/driveway.jpg'
            ],
            [
                'name' => 'Outdoor & Exterior',
                'image_path' => '/storage/locations/exterior.jpg'
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
