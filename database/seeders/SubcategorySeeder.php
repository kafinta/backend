<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
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
        $Kitchenware = Category::where('name', 'Kitchenware')->first();
        $fixtures = Category::where('name', 'Plumbing Fixtures')->first();
        $office = Category::where('name', 'Home office')->first();
        $storage = Category::where('name', 'Storage solutions')->first();
        $outdoor = Category::where('name', 'Outdoor & Gardening')->first();
        $tools = Category::where('name', 'Hardware & Tools')->first();
        $improvement = Category::where('name', 'Home Improvement')->first();
        $hygiene = Category::where('name', 'Personal Care & Hygiene')->first();


        $furniture->subcategories()->create(['name' => 'Sofas & Sectionals']);
        $furniture->subcategories()->create(['name' => 'Chairs']);
        $furniture->subcategories()->create(['name' => 'Tables']);
        $furniture->subcategories()->create(['name' => 'Desks']);
        $furniture->subcategories()->create(['name' => 'Beds']);
        $furniture->subcategories()->create(['name' => 'Futons & Accessories']);
        $furniture->subcategories()->create(['name' => 'Footstools and Ottomans']);
        $furniture->subcategories()->create(['name' => 'Cushions']);
        $furniture->subcategories()->create(['name' => 'Chests & drawers']);
        $furniture->subcategories()->create(['name' => 'Headboards']);
        $furniture->subcategories()->create(['name' => 'Mattresses']);
        $furniture->subcategories()->create(['name' => 'Bed frames']);
        $furniture->subcategories()->create(['name' => 'Benches']);
        $furniture->subcategories()->create(['name' => 'Vanities']);
        $furniture->subcategories()->create(['name' => 'Buffets & sideboards']);
        $furniture->subcategories()->create(['name' => 'Carts']);
        $furniture->subcategories()->create(['name' => 'Racks']);
        $furniture->subcategories()->create(['name' => 'Hammock']);
        $furniture->subcategories()->create(['name' => 'Swing chairs']);
        $furniture->subcategories()->create(['name' => 'Chaise lounges']);
        $furniture->subcategories()->create(['name' => 'Porch Swings']);
        $furniture->subcategories()->create(['name' => 'Hall trees']);
        $furniture->subcategories()->create(['name' => 'Umbrellas and coatracks']);


        $lighting->subcategories()->create(['name' => 'Chandeliers']);
        $lighting->subcategories()->create(['name' => 'Pendant Lights']);
        $lighting->subcategories()->create(['name' => 'Recessed Lights']);
        $lighting->subcategories()->create(['name' => 'Track Lights']);
        $lighting->subcategories()->create(['name' => 'Ceiling Lights']);
        $lighting->subcategories()->create(['name' => 'Mount Lights']);
        $lighting->subcategories()->create(['name' => 'Sconces']);
        $lighting->subcategories()->create(['name' => 'Lamps']);
        $lighting->subcategories()->create(['name' => 'Bulbs']);
        $lighting->subcategories()->create(['name' => 'Lamp shades']);
        $lighting->subcategories()->create(['name' => 'LEDs']);


        $decor->subcategories()->create(['name' => 'Rugs']);
        $decor->subcategories()->create(['name' => 'Pillows & Throws']);
        $decor->subcategories()->create(['name' => 'Artworks']);
        $decor->subcategories()->create(['name' => 'Wall Decals']);
        $decor->subcategories()->create(['name' => 'Wall Sculptures']);
        $decor->subcategories()->create(['name' => 'Wall Panels']);
        $decor->subcategories()->create(['name' => 'Tapestries']);
        $decor->subcategories()->create(['name' => 'Mirrors']);
        $decor->subcategories()->create(['name' => 'Candles & Fragrances']);
        $decor->subcategories()->create(['name' => 'Screen & Room Dividers']);
        $decor->subcategories()->create(['name' => 'Bookends']);
        $decor->subcategories()->create(['name' => 'Plants & Flowers']);
        $decor->subcategories()->create(['name' => 'Plant pots']);
        $decor->subcategories()->create(['name' => 'Fountains']);
        $decor->subcategories()->create(['name' => 'Blinds and Shades']);
        $decor->subcategories()->create(['name' => 'Curtain Rods']);
        $decor->subcategories()->create(['name' => 'Shutters']);
        $decor->subcategories()->create(['name' => 'Valances']);
        $decor->subcategories()->create(['name' => 'Window Film']);
        $decor->subcategories()->create(['name' => 'Stained Glass Panels']);
        $decor->subcategories()->create(['name' => 'Vases']);
        $decor->subcategories()->create(['name' => 'Accent Stools']);


        $entertainment->subcategories()->create(['name' => 'TVs']);
        $entertainment->subcategories()->create(['name' => 'Media Players']);
        $entertainment->subcategories()->create(['name' => 'Audio Players']);
        $entertainment->subcategories()->create(['name' => 'Game Consoles']);


        $fabrics->subcategories()->create(['name' => 'Duvet Covers']);
        $fabrics->subcategories()->create(['name' => 'Comforters']);
        $fabrics->subcategories()->create(['name' => 'Sheets & Blankets']);
        $fabrics->subcategories()->create(['name' => 'Quilts & Bedspreads']);
        $fabrics->subcategories()->create(['name' => 'Duvet Inserts']);
        $fabrics->subcategories()->create(['name' => 'Mattress Toppers']);
        $fabrics->subcategories()->create(['name' => 'Mattress Covers']);
        $fabrics->subcategories()->create(['name' => 'Bed Pillows']);
        $fabrics->subcategories()->create(['name' => 'Bedskirts']);
        $fabrics->subcategories()->create(['name' => 'Curtains']);
        $fabrics->subcategories()->create(['name' => 'Furniture Covers']);


        $appliances->subcategories()->create(['name' => 'Dishwashers']);
        $appliances->subcategories()->create(['name' => 'Freezers']);
        $appliances->subcategories()->create(['name' => 'Refigerators']);
        $appliances->subcategories()->create(['name' => 'Ovens & Microwaves']);
        $appliances->subcategories()->create(['name' => 'Cooktops']);
        $appliances->subcategories()->create(['name' => 'Ice makers']);
        $appliances->subcategories()->create(['name' => 'Blenders']);
        $appliances->subcategories()->create(['name' => 'Toasters']);
        $appliances->subcategories()->create(['name' => 'Bread Machines']);
        $appliances->subcategories()->create(['name' => 'Coffee & Tea Makers']);
        $appliances->subcategories()->create(['name' => 'Deep Fryers']);
        $appliances->subcategories()->create(['name' => 'Grills & Skillets']);
        $appliances->subcategories()->create(['name' => 'Fondue & Raclette Sets']);
        $appliances->subcategories()->create(['name' => 'Dehydrators']);
        $appliances->subcategories()->create(['name' => 'Hot Plates & Burners']);
        $appliances->subcategories()->create(['name' => 'Ice Cream Makers']);
        $appliances->subcategories()->create(['name' => 'Juicers']);
        $appliances->subcategories()->create(['name' => 'Mixers']);
        $appliances->subcategories()->create(['name' => 'Popcorn Makers']);
        $appliances->subcategories()->create(['name' => 'Waffle Makers']);
        $appliances->subcategories()->create(['name' => 'Steamers']);
        $appliances->subcategories()->create(['name' => 'Washing Machines']);
        $appliances->subcategories()->create(['name' => 'Dryers']);
        $appliances->subcategories()->create(['name' => 'Irons']);
        $appliances->subcategories()->create(['name' => 'Garment Steamers']);
        $appliances->subcategories()->create(['name' => 'Vacuum Cleaners']);
        $appliances->subcategories()->create(['name' => 'Carpet and Steam Cleaners']);


        $Kitchenware->subcategories()->create(['name' => 'Pot Fillers']);
        $Kitchenware->subcategories()->create(['name' => 'Water Dispensers']);
        $Kitchenware->subcategories()->create(['name' => 'Baking Tools']);
        $Kitchenware->subcategories()->create(['name' => 'Bakeware Sets']);
        $Kitchenware->subcategories()->create(['name' => 'Baking Dishes']);
        $Kitchenware->subcategories()->create(['name' => 'Cookie Sheets']);
        $Kitchenware->subcategories()->create(['name' => 'Cookie Cutters']);
        $Kitchenware->subcategories()->create(['name' => 'Pans']);
        $Kitchenware->subcategories()->create(['name' => 'Kettles']);
        $Kitchenware->subcategories()->create(['name' => 'Griddles & Skillets']);
        $Kitchenware->subcategories()->create(['name' => 'Can Openers']);
        $Kitchenware->subcategories()->create(['name' => 'Colanders & Strainers']);
        $Kitchenware->subcategories()->create(['name' => 'Cooking Utensils']);
        $Kitchenware->subcategories()->create(['name' => 'Cooking Boards']);
        $Kitchenware->subcategories()->create(['name' => 'Graters, Peelers & Slicers']);
        $Kitchenware->subcategories()->create(['name' => 'Mixing Bowls']);
        $Kitchenware->subcategories()->create(['name' => 'Spoon Rests']);
        $Kitchenware->subcategories()->create(['name' => 'Plates & Bowls']);
        $Kitchenware->subcategories()->create(['name' => 'Glasses, Cups & Mugs']);
        $Kitchenware->subcategories()->create(['name' => 'Beverage Dispensers']);
        $Kitchenware->subcategories()->create(['name' => 'Carafes']);
        $Kitchenware->subcategories()->create(['name' => 'Pitchers']);
        $Kitchenware->subcategories()->create(['name' => 'Platters']);
        $Kitchenware->subcategories()->create(['name' => 'Gravy Boats']);
        $Kitchenware->subcategories()->create(['name' => 'Serving Utensils']);
        $Kitchenware->subcategories()->create(['name' => 'Serving Trays']);
        $Kitchenware->subcategories()->create(['name' => 'Serving Bowls']);
        $Kitchenware->subcategories()->create(['name' => 'Tureens']);
        $Kitchenware->subcategories()->create(['name' => 'Fruit Bowls & Baskets']);
        $Kitchenware->subcategories()->create(['name' => 'Eating Utensils']);


        $fixtures->subcategories()->create(['name' => 'Bidets']);
        $fixtures->subcategories()->create(['name' => 'Sinks']);
        $fixtures->subcategories()->create(['name' => 'Urinals']);
        $fixtures->subcategories()->create(['name' => 'Faucets']);
        $fixtures->subcategories()->create(['name' => 'Showerheads']);
        $fixtures->subcategories()->create(['name' => 'Body Sprays']);
        $fixtures->subcategories()->create(['name' => 'Water heaters']);
        $fixtures->subcategories()->create(['name' => 'Steam Showers']);
        $fixtures->subcategories()->create(['name' => 'Drains']);
        $fixtures->subcategories()->create(['name' => 'Bathtubs']);
        $fixtures->subcategories()->create(['name' => 'Toilets']);


        $office->subcategories()->create(['name' => 'Computers']);
        $office->subcategories()->create(['name' => 'Printers']);
        $office->subcategories()->create(['name' => 'Desks']);
        $office->subcategories()->create(['name' => 'Chairs']);
        $office->subcategories()->create(['name' => 'Bookshelves']);


        $storage->subcategories()->create(['name' => 'Bookcases']);
        $storage->subcategories()->create(['name' => 'Buffets & Sideboards']);
        $storage->subcategories()->create(['name' => 'Dressers']);
        $storage->subcategories()->create(['name' => 'Chests & Cabinets']);
        $storage->subcategories()->create(['name' => 'Armoires & Wardrobes']);
        $storage->subcategories()->create(['name' => 'Display & Wall Shelves']);
        $storage->subcategories()->create(['name' => 'Carts']);
        $storage->subcategories()->create(['name' => 'Wall Organizers']);
        $storage->subcategories()->create(['name' => 'Makeup Vanities']);
        $storage->subcategories()->create(['name' => 'Clothes Racks']);
        $storage->subcategories()->create(['name' => 'Clothes Hangers']);
        $storage->subcategories()->create(['name' => 'Clotheslines']);
        $storage->subcategories()->create(['name' => 'Wine Racks']);
        $storage->subcategories()->create(['name' => 'Pot Racks']);
        $storage->subcategories()->create(['name' => 'Napkin Holders']);
        $storage->subcategories()->create(['name' => 'Shoe Storage']);
        $storage->subcategories()->create(['name' => 'Deck Boxes']);
        $storage->subcategories()->create(['name' => 'Hose Reels']);
        $storage->subcategories()->create(['name' => 'Trash & Recycling']);


        $outdoor->subcategories()->create(['name' => 'Porch Swings']);
        $outdoor->subcategories()->create(['name' => 'Hammocks & Swing Chairs']);
        $outdoor->subcategories()->create(['name' => 'Chaise Lounges']);
        $outdoor->subcategories()->create(['name' => 'Fire pits']);
        $outdoor->subcategories()->create(['name' => 'Chimineas']);
        $outdoor->subcategories()->create(['name' => 'Hot tubs']);
        $outdoor->subcategories()->create(['name' => 'Saunas']);
        $outdoor->subcategories()->create(['name' => 'Swimming Pools']);
        $outdoor->subcategories()->create(['name' => 'Pool toys & floats']);
        $outdoor->subcategories()->create(['name' => 'Pots & Planters']);
        $outdoor->subcategories()->create(['name' => 'Compost Bins']);
        $outdoor->subcategories()->create(['name' => 'Gardening Tools']);
        $outdoor->subcategories()->create(['name' => 'Watering & Irrigation']);
        $outdoor->subcategories()->create(['name' => 'Watering & Irrigation']);


        $tools->subcategories()->create(['name' => 'Wrenches']);
        $tools->subcategories()->create(['name' => 'Screwdrivers']);
        $tools->subcategories()->create(['name' => 'Hammers']);
        $tools->subcategories()->create(['name' => 'Saws']);
        $tools->subcategories()->create(['name' => 'Pliers']);
        $tools->subcategories()->create(['name' => 'Drills']);
        $tools->subcategories()->create(['name' => 'Nuts & Bolts']);
        $tools->subcategories()->create(['name' => 'Latches & Locks']);
        $tools->subcategories()->create(['name' => 'Hinges']);
        $tools->subcategories()->create(['name' => 'Washers']);
        $tools->subcategories()->create(['name' => 'Nails']);
        $tools->subcategories()->create(['name' => 'Screws']);
        $tools->subcategories()->create(['name' => 'Tape Measures']);
        $tools->subcategories()->create(['name' => 'Rulers']);
        $tools->subcategories()->create(['name' => 'Levels']);
        $tools->subcategories()->create(['name' => 'Protractors']);
        $tools->subcategories()->create(['name' => 'Clamps']);
        $tools->subcategories()->create(['name' => 'Cutters & Snips']);
        $tools->subcategories()->create(['name' => 'Glue guns & Adhesives']);
        $tools->subcategories()->create(['name' => 'Safety Equipments']);
        $tools->subcategories()->create(['name' => 'Knobs & Pulls']);


        $improvement->subcategories()->create(['name' => 'Doors']);
        $improvement->subcategories()->create(['name' => 'Windows']);
        $improvement->subcategories()->create(['name' => 'Air Conditioners']);
        $improvement->subcategories()->create(['name' => 'Ceiling Fans']);
        $improvement->subcategories()->create(['name' => 'Standing Fans']);
        $improvement->subcategories()->create(['name' => 'Fireplace']);
        $improvement->subcategories()->create(['name' => 'Humidifiers & Purifiers']);
        $improvement->subcategories()->create(['name' => 'Space Heaters']);
        $improvement->subcategories()->create(['name' => 'Thermostats']);
        $improvement->subcategories()->create(['name' => 'Bamboo Flooring']);
        $improvement->subcategories()->create(['name' => 'Cork Flooring']);
        $improvement->subcategories()->create(['name' => 'Hardwood Flooring']);
        $improvement->subcategories()->create(['name' => 'Engineered Wood Flooring']);
        $improvement->subcategories()->create(['name' => 'Laminate Flooring']);
        $improvement->subcategories()->create(['name' => 'Vinyl Flooring']);
        $improvement->subcategories()->create(['name' => 'Floor Medallions & Inlays']);
        $improvement->subcategories()->create(['name' => 'Wall & Floor tiles']);
        $improvement->subcategories()->create(['name' => 'Deck tiles']);
        $improvement->subcategories()->create(['name' => 'Siding & Stone Veneers']);
        $improvement->subcategories()->create(['name' => 'Counter Tops']);
        $improvement->subcategories()->create(['name' => 'Stair Parts']);
        $improvement->subcategories()->create(['name' => 'Wall Panels']);
        $improvement->subcategories()->create(['name' => 'Molding & Trim']);
        $improvement->subcategories()->create(['name' => 'Onlays & Appliques']);
        $improvement->subcategories()->create(['name' => 'Columns & Capitals']);
        $improvement->subcategories()->create(['name' => 'Ceiling Medallions']);
        $improvement->subcategories()->create(['name' => 'Corbels']);
        $improvement->subcategories()->create(['name' => 'Paints']);
        $improvement->subcategories()->create(['name' => 'Primers']);
        $improvement->subcategories()->create(['name' => 'Stains & Varnishes']);
        $improvement->subcategories()->create(['name' => 'Wallpapers']);
        $improvement->subcategories()->create(['name' => 'Wall Stencils']);
        $improvement->subcategories()->create(['name' => 'Painting tools']);
        $improvement->subcategories()->create(['name' => 'Roofing & Gutters']);

        $hygiene->subcategories()->create(['name' => 'Toothpastes']);
        $hygiene->subcategories()->create(['name' => 'Toothbrushes']);
        $hygiene->subcategories()->create(['name' => 'Mouthwash']);
        $hygiene->subcategories()->create(['name' => 'Razors']);
        $hygiene->subcategories()->create(['name' => 'Shaving cream & gels']);
        $hygiene->subcategories()->create(['name' => 'Aftershave lotions']);
        $hygiene->subcategories()->create(['name' => 'Soaps & body washes']);
        $hygiene->subcategories()->create(['name' => 'Lotions & Moisturizers']);
        $hygiene->subcategories()->create(['name' => 'Hand sanitizers']);
        $hygiene->subcategories()->create(['name' => 'Perfumes & colognes']);
        $hygiene->subcategories()->create(['name' => 'Deodorants & antiperspirants']);
        $hygiene->subcategories()->create(['name' => 'Sunscreen']);
        $hygiene->subcategories()->create(['name' => 'Shampoos & conditioners']);
        $hygiene->subcategories()->create(['name' => 'Hair dyes']);
        $hygiene->subcategories()->create(['name' => 'Hair bleaches']);
        $hygiene->subcategories()->create(['name' => 'Hair toners']);
        $hygiene->subcategories()->create(['name' => 'Hairspray']);
        $hygiene->subcategories()->create(['name' => 'Gel']);
        $hygiene->subcategories()->create(['name' => 'Mousse']);
        $hygiene->subcategories()->create(['name' => 'Wax']);
        $hygiene->subcategories()->create(['name' => 'Tampons & pads']);
        $hygiene->subcategories()->create(['name' => 'Pantyliners']);
        $hygiene->subcategories()->create(['name' => 'Menstrual cups']);
    }
}