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
                'image_path' => '/locations/living.jpg'
            ],
            [
                'name' => 'Bedroom',
                'image_path' => '/locations/bedroom.jpg'
            ],
            [
                'name' => 'Kitchen',
                'image_path' => '/locations/kitchen.jpg'
            ],
            [
                'name' => 'Dining Room',
                'image_path' => '/locations/dining.jpg'
            ],
            [
                'name' => 'Bathroom',
                'image_path' => '/locations/bathroom.jpg'
            ],
            [
                'name' => 'Entryway & Hallway',
                'image_path' => '/locations/hallway.jpg'
            ],
            [
                'name' => 'Closet & Storage',
                'image_path' => '/locations/closet.jpg'
            ],
            [
                'name' => 'Home Office',
                'image_path' => '/locations/office.jpg'
            ],
            [
                'name' => 'Children\'s Room & Nursery',
                'image_path' => '/locations/nursery.jpg'
            ],
            [
                'name' => 'Garage & Shed',
                'image_path' => '/locations/garage.jpg'
            ],
            [
                'name' => 'Porch',
                'image_path' => '/locations/porch.jpg'
            ],
            [
                'name' => 'Deck & Patio',
                'image_path' => '/locations/deck.jpeg'
            ],
            [
                'name' => 'Pool Area',
                'image_path' => '/locations/pool.jpg'
            ],
            [
                'name' => 'Basement',
                'image_path' => '/locations/basement.jpg'
            ],
            [
                'name' => 'Attic',
                'image_path' => '/locations/attic.jpg'
            ],
            [
                'name' => 'Game Room',
                'image_path' => '/locations/game_room.webp'
            ],
            [
                'name' => 'Laundry Room',
                'image_path' => '/locations/laundry.png'
            ],
            [
                'name' => 'Home theater',
                'image_path' => '/locations/theatre.jpg'
            ],
            [
                'name' => 'Home Gym',
                'image_path' => '/locations/gym.jpg'
            ],
            [
                'name' => 'Driveway & Walkway',
                'image_path' => '/locations/driveway.jpg'
            ],
            [
                'name' => 'Outdoor & Exterior',
                'image_path' => '/locations/exterior.jpg'
            ],
        ];

        foreach ($locations as $location) {
            $location['image_path'] = Storage::url($location['image_path']); // Get the public URL for the image
            Location::create($location);
        }
    }
}
