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
        Location::truncate();
        $locations = [
            [
                'name' => 'Living Room',
                'image' => '/images/locations/living.jpg'
            ],
            [
                'name' => 'Bedroom',
                'image' => '/images/locations/bedroom.jpg'
    
            ],
            [
                'name' => 'Kitchen',
                'image' => '/images/locations/kitchen.jpg'
            ],
            [
                'name' => 'Dining',
                'image' => '/images/locations/dining.jpg'
            ],
            [
                'name' => 'Bathroom',
                'image' => '/images/locations/bathroom.jpg'
            ],
            [
                'name' => 'Hallway',
                'image' => '/images/locations/hallway.jpg'
            ],
            [
                'name' => 'Closet',
                'image' => '/images/locations/closet.jpg'
            ],
            [
                'name' => 'Home Office',
                'image' => '/images/locations/office.jpg'
            ],
            [
                'name' => 'Kids Room',
                'image' => '/images/locations/nursery.jpg'
            ],
            [
                'name' => 'Garage & Shed',
                'image' => '/images/locations/garage.jpg'
            ],
            [
                'name' => 'Porch',
                'image' => '/images/locations/porch.jpg'
            ],
            [
                'name' => 'Deck',
                'image' => '/images/locations/deck.jpeg'
            ],
            [
                'name' => 'Pool Area',
                'image' => '/images/locations/pool.jpg'
            ],
            [
                'name' => 'Patio',
                'image' => '/images/locations/patio.png'
            ],
            [
                'name' => 'Basement',
                'image' => '/images/locations/basement.jpg'
            ],
            [
                'name' => 'Attic',
                'image' => '/images/locations/attic.jpg'
            ],
            [
                'name' => 'Utility Room',
                'image' => '/images/locations/utility.png'
            ],
            [
                'name' => 'Home theater',
                'image' => '/images/locations/theatre.jpg'
            ],
            [
                'name' => 'Home Gym',
                'image' => '/images/locations/gym.jpg'
            ],
            [
                'name' => 'Driveway & Walkway',
                'image' => '/images/locations/driveway.jpg'
            ],
            [
                'name' => 'Exterior',
                'image' => '/images/locations/exterior.jpg'
            ],
            [
                'name' => 'Outdoor',
                'image' => '/images/locations/outdoor.jpg'
            ],
        ];

        foreach ($locations as $location) {
            $location['image'] = Storage::url($location['image']); // Get the public URL for the image
            Location::create($location);
        }
    }
}
