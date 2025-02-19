<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Location;
use App\Models\SubCategory;

class SubcategorySeeder extends Seeder
{
    public function run()
    {
        $furniture = Category::where('name', 'Furniture')->first();
        $lighting = Category::where('name', 'Lighting')->first();
        $decor = Category::where('name', 'Décor')->first();
        $entertainment = Category::where('name', 'Entertainment')->first();
        $fabrics = Category::where('name', 'Fabrics')->first();
        $appliances = Category::where('name', 'Appliances')->first();
        $fixtures = Category::where('name', 'Fixtures')->first();
        $storage = Category::where('name', 'Storage solutions')->first();
        $outdoor = Category::where('name', 'Outdoor & Gardening')->first();
        $tools = Category::where('name', 'Hardware & Tools')->first();
        $improvement = Category::where('name', 'Home Improvement')->first();
        $hygiene = Category::where('name', 'Personal Care & Hygiene')->first();



        $living = Location::where('name', 'Living Room')->first();
        $bedroom = Location::where('name', 'Bedroom')->first();
        $kitchen = Location::where('name', 'Kitchen')->first();
        $diningroom = Location::where('name', 'Dining Room')->first();
        $bathroom = Location::where('name', 'Bathroom')->first();
        $hall = Location::where('name', 'Entryway & Hallway')->first();
        $closet = Location::where('name', 'Closet & Storage')->first();
        $office = Location::where('name', 'Home Office')->first();
        $kids = Location::where('name', 'Children\'s Room & Nursery')->first();
        $garage = Location::where('name', 'Garage & Shed')->first();
        $porch = Location::where('name', 'Porch')->first();
        $deck = Location::where('name', 'Deck')->first();
        $pool = Location::where('name', 'Pool Area')->first();
        $basement = Location::where('name', 'Basement')->first();
        $attic = Location::where('name', 'Attic')->first();
        $game = Location::where('name', 'Game Room')->first();
        $laundry = Location::where('name', 'Laundry Room')->first();
        $theater = Location::where('name', 'Home theater')->first();
        $gym = Location::where('name', 'Home Gym')->first();
        $driveway = Location::where('name', 'Driveway & Walkway')->first();
        $exterior = Location::where('name', 'Outdoor & Exterior')->first();

        Subcategory::create([
            'name' => 'Sofas and Couches',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Sectional Sofas',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Love Seats',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Sleeper Sofas/Sofa Beds',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Futons',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Futon Covers',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Futon Frames',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Futon Mattresses',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Coffee Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Side & End Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Console Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $hall->id]);
        
        Subcategory::create([
            'name' => 'Armchairs and Accent Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Ottomans and Footrests',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Accent Chests & Cabinets',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Media Storage',
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Table Sets',
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $diningroom->id]);

        Subcategory::create([
            'name' => 'Furniture Sets',
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $bedroom->id, $diningroom->id, $game->id, $kids->id]);

        Subcategory::create([
            'name' => 'Beds',
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $kids->id]);

        Subcategory::create([
            'name' => 'Dressers & Chests',
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);

        Subcategory::create([
            'name' => 'Nightstands & Bedside Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $kids->id]);

        Subcategory::create([
            'name' => 'Headboards',
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Bedframes',
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Mattresses',
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Benches',
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $hall->id]);
        
        Subcategory::create([
            'name' => 'Buffets & Sideboards',
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);

        Subcategory::create([
            'name' => 'Islands and Carts',
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);
    
        Subcategory::create([
            'name' => 'China Cabinets and Hutches',
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);
    
        Subcategory::create([
            'name' => 'Dining Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach($diningroom->id);
    
        Subcategory::create([
            'name' => 'Dining Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach($diningroom->id);
    
        Subcategory::create([
            'name' => 'Bar Stools & Counter Stools',
            'category_id' => $furniture->id,
        ])->locations()->attach([$diningroom->id, $game->id]);
    
        Subcategory::create([
            'name' => 'Desks',
            'category_id' => $furniture->id,
        ])->locations()->attach([$office->id, $kids->id]);
    
        Subcategory::create([
            'name' => 'Office Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach($office->id);
    
        Subcategory::create([
            'name' => 'Bookcases',
            'category_id' => $storage->id,
        ])->locations()->attach([$office->id, $kids->id]);
    
        Subcategory::create([
            'name' => 'Filing Cabinets',
            'category_id' => $furniture->id,
        ])->locations()->attach($office->id);
    
        Subcategory::create([
            'name' => 'Coatracks & Umbrella Stands',
            'category_id' => $furniture->id,
        ])->locations()->attach($hall->id);
    
        Subcategory::create([
            'name' => 'Hall Trees',
            'category_id' => $furniture->id,
        ])->locations()->attach($hall->id);
    
        Subcategory::create([
            'name' => 'Gaming Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id]);
    
        Subcategory::create([
            'name' => 'Bean Bag Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id]);
    
        Subcategory::create([
            'name' => 'Theater Seating',
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id, $theater->id]);
    
        Subcategory::create([
            'name' => 'Gaming Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach($game->id);
    
        Subcategory::create([
            'name' => 'Gaming Chairs',
            'category_id' => $furniture->id,
        ])->locations()->attach($game->id);
    
        Subcategory::create([
            'name' => 'Bunk Beds',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Loft Beds',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Kids Seating',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
            
        Subcategory::create([
            'name' => 'Toy Organizers',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);

        Subcategory::create([
            'name' => 'Cribs',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Gliders',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Cradles & Bassinets',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
            
        Subcategory::create([
            'name' => 'High Chair & Booster Seats',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Changing Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Changing Tables',
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Lift Chairs',
            'category_id' => $furniture->id,
        ]);

        Subcategory::create([
            'name' => 'Adjustable Beds',
            'category_id' => $furniture->id,
        ]);
        
        Subcategory::create([
            'name' => 'Cabinets',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Shelves',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Dressers',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Media Storage',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Armoires & Wardrobes',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Racks',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Accent Chests & Cabinets',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Buffets & Sideboards',
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Vanities',
            'category_id' => $storage->id,
        ])->locations()->attach([$bathroom->id, $bedroom->id]);
        
        Subcategory::create([
            'name' => 'Vanity Tops and Side Splashes',
            'category_id' => $storage->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Organizers',
            'category_id' => $storage->id,
        ])->locations()->attach([$bedroom->id, $living->id, $kitchen->id, $laundry->id, $bathroom->id]);
        
        Subcategory::create([
            'name' => 'Faucets',
            'category_id' => $fixtures->id,
        ])->locations()->attach([$bathroom->id, $kitchen->id]);
        
        Subcategory::create([
            'name' => 'Bathtubs',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Sinks',
            'category_id' => $fixtures->id,
        ])->locations()->attach([$bathroom->id, $kitchen->id]);
        
        Subcategory::create([
            'name' => 'Toilets',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Bidets',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Shower Stalls & Kits',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Shower Pans & Bases',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Steam Showers',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Showerheads & Body Sprays',
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Rugs',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Doormats',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Rug Pads',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Runner Rugs',
            'category_id' => $decor->id,
        ])->locations()->attach($hall->id);
        
        Subcategory::create([
            'name' => 'Home Fragrances',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Plant Stands & Tables',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Pillows & Throws',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Vases',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'VasePlants, Pots & Fountainss',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Artificial Flowers & Plants',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Screens & Room Dividers',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Decorative Jars & Urns',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Bookends',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'World Globes',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Picture Frames',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Clocks',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Mirrors',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Sculptures & Statues',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wall Panels',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wallpapers',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Weather Vanes',
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wind Chimes',
            'category_id' => $decor->id,
        ]);
        
    }
}