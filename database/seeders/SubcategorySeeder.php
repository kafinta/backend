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
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Sectional Sofas',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Love Seats',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Sleeper Sofas/Sofa Beds',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Futons',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Futon Covers',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Futon Frames',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Futon Mattresses',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Coffee & Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);

        Subcategory::create([
            'name' => 'Side & End Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Console Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $hall->id]);
        
        Subcategory::create([
            'name' => 'Armchairs and Accent Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Ottomans and Footrests',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Accent Chests & Cabinets',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Media Storage',
            'has_colors' => false,
            'category_id' => $furniture->id,
        ])->locations()->attach($living->id);
        
        Subcategory::create([
            'name' => 'Table Sets',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $diningroom->id]);

        Subcategory::create([
            'name' => 'Furniture Sets',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$living->id, $bedroom->id, $diningroom->id, $game->id, $kids->id]);

        Subcategory::create([
            'name' => 'Beds',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $kids->id]);

        Subcategory::create([
            'name' => 'Dressers & Chests',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);

        Subcategory::create([
            'name' => 'Nightstands & Bedside Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $kids->id]);

        Subcategory::create([
            'name' => 'Headboards',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Bedframes',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Mattresses',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($bedroom->id);
        
        Subcategory::create([
            'name' => 'Benches',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$bedroom->id, $hall->id]);
        
        Subcategory::create([
            'name' => 'Buffets & Sideboards',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);

        Subcategory::create([
            'name' => 'Islands and Carts',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);
    
        Subcategory::create([
            'name' => 'China Cabinets and Hutches',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kitchen->id);
    
        Subcategory::create([
            'name' => 'Dining Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($diningroom->id);
    
        Subcategory::create([
            'name' => 'Dining Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($diningroom->id);
    
        Subcategory::create([
            'name' => 'Bar Stools & Counter Stools',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$diningroom->id, $game->id]);
    
        Subcategory::create([
            'name' => 'Desks',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$office->id, $kids->id]);
    
        Subcategory::create([
            'name' => 'Office Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($office->id);
    
        Subcategory::create([
            'name' => 'Bookcases',
            'has_colors' => true,
            'category_id' => $storage->id,
        ])->locations()->attach([$office->id, $kids->id]);
    
        Subcategory::create([
            'name' => 'Filing Cabinets',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($office->id);
    
        Subcategory::create([
            'name' => 'Coatracks & Umbrella Stands',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($hall->id);
    
        Subcategory::create([
            'name' => 'Hall Trees',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($hall->id);
    
        Subcategory::create([
            'name' => 'Gaming Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id]);
    
        Subcategory::create([
            'name' => 'Bean Bag Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id]);
    
        Subcategory::create([
            'name' => 'Theater Seating',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach([$game->id, $living->id, $theater->id]);
    
        Subcategory::create([
            'name' => 'Gaming Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($game->id);
    
        Subcategory::create([
            'name' => 'Gaming Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($game->id);
    
        Subcategory::create([
            'name' => 'Bunk Beds',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Loft Beds',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Kids Seating',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
            
        Subcategory::create([
            'name' => 'Toy Organizers',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);

        Subcategory::create([
            'name' => 'Cribs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Gliders',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Cradles & Bassinets',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
            
        Subcategory::create([
            'name' => 'High Chair & Booster Seats',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Changing Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Changing Tables',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ])->locations()->attach($kids->id);
        
        Subcategory::create([
            'name' => 'Lift Chairs',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ]);

        Subcategory::create([
            'name' => 'Adjustable Beds',
            'has_colors' => true,
            'category_id' => $furniture->id,
        ]);
        
        Subcategory::create([
            'name' => 'Cabinets',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Shelves',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Dressers',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Media Storage',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Armoires & Wardrobes',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Racks',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Accent Chests & Cabinets',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Buffets & Sideboards',
            'has_colors' => true,
            'category_id' => $storage->id,
        ]);
        
        Subcategory::create([
            'name' => 'Vanities',
            'has_colors' => true,
            'category_id' => $storage->id,
        ])->locations()->attach([$bathroom->id, $bedroom->id]);
        
        Subcategory::create([
            'name' => 'Vanity Tops and Side Splashes',
            'has_colors' => true,
            'category_id' => $storage->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Organizers',
            'has_colors' => true,
            'category_id' => $storage->id,
        ])->locations()->attach([$bedroom->id, $living->id, $kitchen->id, $laundry->id, $bathroom->id]);
        
        Subcategory::create([
            'name' => 'Faucets',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach([$bathroom->id, $kitchen->id]);
        
        Subcategory::create([
            'name' => 'Bathtubs',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Sinks',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach([$bathroom->id, $kitchen->id]);
        
        Subcategory::create([
            'name' => 'Toilets',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Bidets',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Shower Stalls & Kits',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Shower Pans & Bases',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Steam Showers',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Showerheads & Body Sprays',
            'has_colors' => true,
            'category_id' => $fixtures->id,
        ])->locations()->attach($bathroom->id);
        
        Subcategory::create([
            'name' => 'Rugs',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Doormats',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Rug Pads',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Runner Rugs',
            'has_colors' => true,
            'category_id' => $decor->id,
        ])->locations()->attach($hall->id);
        
        Subcategory::create([
            'name' => 'Home Fragrances',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Plant Stands & Tables',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Pillows & Throws',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Vases',
            'has_colors' => true,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'VasePlants, Pots & Fountainss',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Artificial Flowers & Plants',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Screens & Room Dividers',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Decorative Jars & Urns',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Bookends',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'World Globes',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Picture Frames',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Clocks',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Mirrors',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Sculptures & Statues',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wall Panels',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wallpapers',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Weather Vanes',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
        Subcategory::create([
            'name' => 'Wind Chimes',
            'has_colors' => false,
            'category_id' => $decor->id,
        ]);
        
    }
}