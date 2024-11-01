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
                'image_path' => '/images/locations/living.jpg'
            ],
            [
                'name' => 'Bedroom',
                'image_path' => '/images/locations/bedroom.jpg'
            ],
            [
                'name' => 'Kitchen',
                'image_path' => '/images/locations/kitchen.jpg'
            ],
            [
                'name' => 'Dining Room',
                'image_path' => '/images/locations/dining.jpg'
            ],
            [
                'name' => 'Bathroom',
                'image_path' => '/images/locations/bathroom.jpg'
            ],
            [
                'name' => 'Entryway & Hallway',
                'image_path' => '/images/locations/hallway.jpg'
            ],
            [
                'name' => 'Closet & Storage',
                'image_path' => '/images/locations/closet.jpg'
            ],
            [
                'name' => 'Home Office',
                'image_path' => '/images/locations/office.jpg'
            ],
            [
                'name' => 'Children\'s Room',
                'image_path' => '/images/locations/nursery.jpg'
            ],
            [
                'name' => 'Garage & Shed',
                'image_path' => '/images/locations/garage.jpg'
            ],
            [
                'name' => 'Porch',
                'image_path' => '/images/locations/porch.jpg'
            ],
            [
                'name' => 'Deck & Patio',
                'image_path' => '/images/locations/deck.jpeg'
            ],
            [
                'name' => 'Pool Area',
                'image_path' => '/images/locations/pool.jpg'
            ],
            [
                'name' => 'Basement',
                'image_path' => '/images/locations/basement.jpg'
            ],
            [
                'name' => 'Attic',
                'image_path' => '/images/locations/attic.jpg'
            ],
            [
                'name' => 'Game Room',
                'image_path' => '/images/locations/game_room.webp'
            ],
            [
                'name' => 'Utility Room',
                'image_path' => '/images/locations/utility.png'
            ],
            [
                'name' => 'Home theater',
                'image_path' => '/images/locations/theatre.jpg'
            ],
            [
                'name' => 'Home Gym',
                'image_path' => '/images/locations/gym.jpg'
            ],
            [
                'name' => 'Driveway & Walkway',
                'image_path' => '/images/locations/driveway.jpg'
            ],
            [
                'name' => 'Outdoor & Exterior',
                'image_path' => '/images/locations/exterior.jpg'
            ],
        ];

        foreach ($locations as $location) {
            $location['image_path'] = Storage::url($location['image_path']); // Get the public URL for the image
            Location::create($location);
        }
    }
}
