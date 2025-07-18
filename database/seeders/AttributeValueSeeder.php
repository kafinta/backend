<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subcategory;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $globalAttributes = [
            'Color' => [
                'is_variant_generator' => true,
                'help_text' => 'Select the primary color of the item',
                'sort_order' => 1,
                'values' => [
                    ['name' => 'Red', 'representation' => ['hex' => '#FF0000']],
                    ['name' => 'Blue', 'representation' => ['hex' => '#0000FF']],
                    ['name' => 'Green', 'representation' => ['hex' => '#00FF00']],
                    ['name' => 'Black', 'representation' => ['hex' => '#000000']],
                    ['name' => 'White', 'representation' => ['hex' => '#FFFFFF']],
                    ['name' => 'Gray', 'representation' => ['hex' => '#808080']],
                    ['name' => 'Brown', 'representation' => ['hex' => '#5B4B3D']],
                    ['name' => 'Beige', 'representation' => ['hex' => '#E3DAC9']],
                    ['name' => 'Yellow', 'representation' => ['hex' => '#FFFF00']],
                    ['name' => 'Orange', 'representation' => ['hex' => '#FF7F00']],
                    ['name' => 'Pink', 'representation' => ['hex' => '#FFC0CB']],
                    ['name' => 'Black & White' ],
                    ['name' => 'Multicolor']
                ]
            ],
            'Style' => [
                'is_variant_generator' => false,
                'help_text' => 'Choose the design style that best describes this item',
                'sort_order' => 2,
                'values' => [
                    ['name' => 'Modern'],
                    ['name' => 'Traditional'],
                    ['name' => 'Contemporary'],
                    ['name' => 'Farmhouse'],
                    ['name' => 'Industrial'],
                    ['name' => 'Transitional'],
                    ['name' => 'Scandinavian'],
                    ['name' => 'Rustic'],
                    ['name' => 'Coastal'],
                    ['name' => 'Eclectic'],
                    ['name' => 'Southwestern'],
                    ['name' => 'Asian'],
                    ['name' => 'Victorian'],
                    ['name' => 'Mediterranean'],
                    ['name' => 'Tropical']
                ]
            ],
            'Assembly' => [
                'is_variant_generator' => false,
                'help_text' => 'Specify if the item comes assembled or requires assembly',
                'sort_order' => 10,
                'values' => [
                    ['name' => 'Fully Assembled'],
                    ['name' => 'Requires Assembly']
                ]
            ],
        ];

        // Only seed the Sofas and Couches subcategory
        $subcategoryAttributes = [
            'Sofas and Couches' => [
                'Design' => [
                    'help_text' => 'Select the sofa design type.',
                    'values' => [
                        ['name' => 'Chesterfield'],
                        ['name' => 'Curved'],
                        ['name' => 'Standard'],
                    ]
                ],
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the sofa upholstery.',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Canvas'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Chenille'],
                        ['name' => 'Cotton'],
                        ['name' => 'Wool'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Jute & Sisal'],
                        ['name' => 'Silk'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common sofa sizes.',
                    'values' => [
                        ['name' => '6 Feet'],
                        ['name' => '7 Feet'],
                        ['name' => '8 Feet'],
                        ['name' => '9 Feet'],
                        ['name' => '10 Feet'],
                        ['name' => '5 Feet'],
                    ]
                ],
                'Arm Style' => [
                    'help_text' => 'Choose the style of the sofa arms.',
                    'values' => [
                        ['name' => 'Sloped Arms'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Armless'],
                        ['name' => 'Pillow Top Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Tuxedo'],
                        ['name' => 'Flared Arms'],
                    ]
                ],
                'Back Type' => [
                    'help_text' => 'Select the type of sofa back.',
                    'values' => [
                        ['name' => 'Tight Back'],
                        ['name' => 'Channel Back'],
                        ['name' => 'Pillow Back'],
                        ['name' => 'Camel Back'],
                    ]
                ],
                'Back Height' => [
                    'help_text' => 'Choose the height of the sofa back.',
                    'values' => [
                        ['name' => 'High Back'],
                        ['name' => 'Low Back'],
                    ]
                ],
                'Cushion Fill' => [
                    'help_text' => 'Select the type of cushion filling.',
                    'values' => [
                        ['name' => 'Feather & Down'],
                        ['name' => 'Polyester'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Seating Capacity' => [
                    'help_text' => 'Choose the number of seats the sofa provides.',
                    'values' => [
                        ['name' => 'Seats 2'],
                        ['name' => 'Seats 3'],
                        ['name' => 'Seats 4'],
                        ['name' => 'Seats 5'],
                        ['name' => 'Seats 6'],
                        ['name' => 'Seats 8'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the sofa offers.',
                    'values' => [
                        ['name' => 'Extra Long'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Tufted'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Small-Scale'],
                        ['name' => 'Ottoman Included'],
                        ['name' => '8-Way Hand Tied'],
                        ['name' => 'Reclining'],
                        ['name' => 'Distressed Leather'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Skirted'],
                        ['name' => 'Storage'],
                        ['name' => 'Wheels'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the sofa frame.',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Popular Dimensions' => [
                    'help_text' => 'Select the popular sofa dimensions.',
                    'values' => [
                        ['name' => 'Large'],
                        ['name' => 'Narrow'],
                        ['name' => 'Oversized'],
                    ]
                ],
            ],
            'Sectional Sofas' => [
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the sectional sofa upholstery.',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Chenille'],
                        ['name' => 'Canvas'],
                        ['name' => 'Suede'],
                        ['name' => 'Cotton'],
                        ['name' => 'Jute & Sisal'],
                    ]
                ],
                'Configuration' => [
                    'help_text' => 'Select the sectional sofa configuration.',
                    'values' => [
                        ['name' => 'L-Shaped'],
                        ['name' => 'Curved'],
                        ['name' => 'U-Shaped'],
                    ]
                ],
                'Orientation' => [
                    'help_text' => 'Choose the orientation of the sectional sofa.',
                    'values' => [
                        ['name' => 'Reversible'],
                        ['name' => 'Left-Facing'],
                        ['name' => 'Right-Facing'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common sectional sofa sizes.',
                    'values' => [
                        ['name' => '6 Feet'],
                        ['name' => '7 Feet'],
                        ['name' => '8 Feet'],
                        ['name' => '9 Feet'],
                        ['name' => '10 Feet'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the sectional sofa offers.',
                    'values' => [
                        ['name' => 'Modular'],
                        ['name' => 'Tufted'],
                        ['name' => 'Reclining'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Ottoman Included'],
                        ['name' => 'Storage'],
                        ['name' => 'Stain-Resistant'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Nailhead Trim'],
                    ]
                ],
                'Cushion Fill' => [
                    'help_text' => 'Select the type of cushion filling.',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Feather & Down'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Arm Style' => [
                    'help_text' => 'Choose the style of the sectional sofa arms.',
                    'values' => [
                        ['name' => 'Armless'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Pillow Top Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'One Arm'],
                        ['name' => 'Sloped Arms'],
                    ]
                ],
                'Number in Set' => [
                    'help_text' => 'Select the number of pieces in the sectional set.',
                    'values' => [
                        ['name' => '2 Piece Set'],
                        ['name' => '3 Piece Set'],
                        ['name' => '4 Piece Set'],
                        ['name' => '5 Piece Set'],
                        ['name' => '6 Piece Set'],
                        ['name' => '7 Piece Set'],
                    ]
                ],
                'Back Height' => [
                    'help_text' => 'Choose the height of the sectional sofa back.',
                    'values' => [
                        ['name' => 'Low Back'],
                        ['name' => 'High Back'],
                    ]
                ],
                'Back Cushion Type' => [
                    'help_text' => 'Select the type of back cushion.',
                    'values' => [
                        ['name' => 'Channel Back'],
                        ['name' => 'Pillow Back'],
                        ['name' => 'Tight Back'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the sectional sofa frame.',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
            ],
            'Love Seats' => [
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the loveseat upholstery.',
                    'values' => [
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Chenille'],
                        ['name' => 'Acrylic'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the loveseat offers.',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Reclining'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Storage'],
                        ['name' => 'Wheels'],
                    ]
                ],
                'Arm Style' => [
                    'help_text' => 'Choose the style of the loveseat arms.',
                    'values' => [
                        ['name' => 'Square Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Flared Arms'],
                        ['name' => 'Armless'],
                        ['name' => 'One Arm'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the loveseat frame.',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
            ],
            'Sleeper Sofas/Sofa Beds' => [
                'Mattress Type' => [
                    'help_text' => 'Select the type of mattress included with the sleeper sofa or sofa bed.',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Innerspring'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Mattress Size' => [
                    'help_text' => 'Choose the size of the mattress for the sleeper sofa or sofa bed.',
                    'values' => [
                        ['name' => 'Queen'],
                        ['name' => 'Twin'],
                        ['name' => 'Full & Double'],
                        ['name' => 'King'],
                    ]
                ],
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the sleeper sofa or sofa bed upholstery.',
                    'values' => [
                        ['name' => 'Velvet'],
                        ['name' => 'Linen'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Leather'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the sleeper sofa or sofa bed offers.',
                    'values' => [
                        ['name' => 'Storage'],
                        ['name' => 'Tufted'],
                        ['name' => 'Reclining'],
                    ]
                ],
                'Cushion Fill' => [
                    'help_text' => 'Select the type of cushion filling.',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Arm Style' => [
                    'help_text' => 'Choose the style of the sleeper sofa or sofa bed arms.',
                    'values' => [
                        ['name' => 'Square Arms'],
                        ['name' => 'Armless'],
                        ['name' => 'Round Arms'],
                    ]
                ],
            ],
            'Coffee Tables' => [
                'Shape' => [
                    'help_text' => 'Select the shape of the coffee table.',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Novelty'],
                        ['name' => 'Free-Form'],
                        ['name' => 'Octagon'],
                        ['name' => 'Hexagon'],
                        ['name' => 'Triangle'],
                        ['name' => 'Kidney'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Choose the wood tone for the coffee table.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common coffee table sizes.',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => 'More Options'],
                        ['name' => '30 Inch'],
                        ['name' => '36 Inch'],
                        ['name' => '40 Inch'],
                        ['name' => '42 Inch'],
                        ['name' => '48 Inch'],
                        ['name' => '60 Inch'],
                    ]
                ],
                'Top Material' => [
                    'help_text' => 'Choose the material used for the coffee table top.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Laminate'],
                        ['name' => 'Stone'],
                        ['name' => 'Fabric'],
                        ['name' => 'Concrete'],
                        ['name' => 'Mirror'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Smoked Glass'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the coffee table base.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Driftwood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Base Design' => [
                    'help_text' => 'Select the design of the coffee table base.',
                    'values' => [
                        ['name' => 'Drum'],
                        ['name' => 'Pedestal'],
                        ['name' => 'Solid Base'],
                        ['name' => 'Tripod'],
                        ['name' => 'Waterfall'],
                        ['name' => 'Trestle'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the coffee table.',
                    'values' => [
                        ['name' => 'Natural Finish'],
                        ['name' => 'Gold'],
                        ['name' => 'Transparent'],
                        ['name' => 'Walnut'],
                        ['name' => 'Oak'],
                        ['name' => 'Silver'],
                        ['name' => 'Espresso'],
                        ['name' => 'Bronze'],
                        ['name' => 'Cherry'],
                        ['name' => 'Copper'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Maple'],
                        ['name' => 'Pine'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Rose Gold'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the coffee table offers.',
                    'values' => [
                        ['name' => 'Tray Top'],
                        ['name' => 'Shelf'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Oversized'],
                        ['name' => 'Drawers'],
                        ['name' => 'Lift Top'],
                        ['name' => 'Geometric'],
                        ['name' => 'Reclaimed Wood'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Casters'],
                        ['name' => 'Carved'],
                        ['name' => 'Bunching'],
                        ['name' => 'Live Edge'],
                        ['name' => 'Hand-Painted'],
                        ['name' => 'Turned Legs'],
                        ['name' => 'Trunk'],
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Nesting'],
                        ['name' => 'Whitewashed'],
                        ['name' => 'Narrow'],
                        ['name' => 'Magazine Holder'],
                    ]
                ],
            ],
            'Side & End Tables' => [
                'Popular Sizes' => [
                    'help_text' => 'Select the most common side and end table sizes.',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => 'Tall'],
                        ['name' => 'More Options'],
                        ['name' => '20 Inch'],
                        ['name' => '24 Inch'],
                        ['name' => '28 Inch'],
                        ['name' => '30 Inch'],
                        ['name' => '36 Inch'],
                    ]
                ],
                'Shape' => [
                    'help_text' => 'Select the shape of the side or end table.',
                    'values' => [
                        ['name' => 'Square'],
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Oval'],
                        ['name' => 'Cube'],
                        ['name' => 'Hexagon'],
                        ['name' => 'Octagon'],
                        ['name' => 'Triangle'],
                        ['name' => 'Wedge'],
                        ['name' => 'Semicircle'],
                    ]
                ],
                'Tabletop Material' => [
                    'help_text' => 'Choose the material used for the tabletop.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Marble'],
                        ['name' => 'Stone'],
                        ['name' => 'Concrete'],
                        ['name' => 'Granite'],
                        ['name' => 'Mosaic'],
                        ['name' => 'Leather'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the table base.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Ceramic'],
                        ['name' => 'Concrete'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                        ['name' => 'Wrought Iron'],
                    ]
                ],
                'Base Design' => [
                    'help_text' => 'Select the design of the table base.',
                    'values' => [
                        ['name' => 'Pedestal'],
                        ['name' => 'Drum'],
                        ['name' => 'C-Shaped'],
                        ['name' => 'Solid Base'],
                        ['name' => 'Tripod'],
                        ['name' => 'Sled'],
                        ['name' => 'Waterfall'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the side or end table.',
                    'values' => [
                        ['name' => 'Gold'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Silver'],
                        ['name' => 'Walnut'],
                        ['name' => 'Bronze'],
                        ['name' => 'Cherry'],
                        ['name' => 'Espresso'],
                        ['name' => 'Transparent'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Copper'],
                        ['name' => 'Maple'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Rose Gold'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the side or end table offers.',
                    'values' => [
                        ['name' => 'Storage'],
                        ['name' => 'Drawers'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Shelf'],
                        ['name' => 'Tray Top'],
                        ['name' => 'Geometric'],
                        ['name' => 'Woven'],
                        ['name' => 'Casters'],
                        ['name' => 'Nesting'],
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Charging Station'],
                        ['name' => 'Hand-Painted'],
                        ['name' => 'Three-Tier'],
                        ['name' => 'Flip Top'],
                        ['name' => 'Magazine Holder'],
                        ['name' => 'Folding'],
                        ['name' => 'Drop Leaf'],
                        ['name' => 'Lift Top'],
                    ]
                ],
            ],
            'Console Tables' => [
                'Popular Sizes' => [
                    'help_text' => 'Select the most common console table sizes.',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => 'More Options'],
                        ['name' => '36 Inch'],
                        ['name' => '50 Inch'],
                        ['name' => '60 Inch'],
                        ['name' => '70 Inch'],
                        ['name' => '72 Inch'],
                        ['name' => '80 Inch'],
                    ]
                ],
                'Tabletop Material' => [
                    'help_text' => 'Choose the material used for the tabletop.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Mirror'],
                        ['name' => 'Marble'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Concrete'],
                        ['name' => 'Leather'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Shape' => [
                    'help_text' => 'Select the shape of the console table.',
                    'values' => [
                        ['name' => 'Rectangle'],
                        ['name' => 'Semicircle'],
                        ['name' => 'Square'],
                        ['name' => 'Round'],
                        ['name' => 'Oval'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the console table.',
                    'values' => [
                        ['name' => 'Transparent'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Gold'],
                        ['name' => 'Silver'],
                        ['name' => 'Maple'],
                        ['name' => 'Walnut'],
                        ['name' => 'Oak'],
                        ['name' => 'Bronze'],
                        ['name' => 'Espresso'],
                        ['name' => 'Cherry'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Pine'],
                        ['name' => 'Unfinished'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the console table offers.',
                    'values' => [
                        ['name' => 'Drawers'],
                        ['name' => 'Shelf'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Extra-Long'],
                        ['name' => 'Casters'],
                        ['name' => 'Hand-Painted'],
                        ['name' => 'Flip Top'],
                        ['name' => 'Nesting'],
                    ]
                ],
                'Table Base Material' => [
                    'help_text' => 'Choose the material used for the table base.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Base Design' => [
                    'help_text' => 'Select the design of the table base.',
                    'values' => [
                        ['name' => 'Pedestal'],
                        ['name' => 'Waterfall'],
                        ['name' => 'Trestle'],
                    ]
                ],
            ],
            'Armchairs and Accent Chairs' => [
                'Chair Design' => [
                    'help_text' => 'Select the design or style of the armchair or accent chair.',
                    'values' => [
                        ['name' => 'Side Chair'],
                        ['name' => 'Lounge Chair'],
                        ['name' => 'Club Chair'],
                        ['name' => 'Barrel Chair'],
                        ['name' => 'Wingback Chair'],
                        ['name' => 'Corner Chair'],
                        ['name' => 'Slipper Chair'],
                        ['name' => 'Papasan Chair'],
                        ['name' => 'Chair & A Half'],
                        ['name' => 'Specialty Accent Chair'],
                        ['name' => 'Egg Chair'],
                        ['name' => 'Balloon Chair'],
                        ['name' => 'Armchair'],
                    ]
                ],
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the chair upholstery.',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Cotton'],
                        ['name' => 'Chenille'],
                        ['name' => 'Wool'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Cotton Blend'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Synthetic'],
                        ['name' => 'None'],
                        ['name' => 'Jute & Sisal'],
                        ['name' => 'Suede'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the chair offers.',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Swivel'],
                        ['name' => 'Arm Pads'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Stacking'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Ottoman Included'],
                        ['name' => 'Small Scale'],
                        ['name' => 'Oversized'],
                        ['name' => 'Wheels'],
                        ['name' => 'Reclining'],
                        ['name' => 'Slipcovered'],
                    ]
                ],
                'Width' => [
                    'help_text' => 'Select the width range of the chair.',
                    'values' => [
                        ['name' => '20 To 24 Inches'],
                        ['name' => '25 To 29 Inches'],
                        ['name' => '30 To 34 Inches'],
                        ['name' => '35 To 39 Inches'],
                        ['name' => '40 To 44 Inches'],
                        ['name' => '45 To 49 Inches'],
                    ]
                ],
                'Back Height' => [
                    'help_text' => 'Choose the back height of the chair.',
                    'values' => [
                        ['name' => 'Mid Back'],
                        ['name' => 'Low Back'],
                        ['name' => 'High Back'],
                    ]
                ],
                'Arm Type' => [
                    'help_text' => 'Select the type of arms for the chair.',
                    'values' => [
                        ['name' => 'Square Arms'],
                        ['name' => 'Armless'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Sloped Arms'],
                        ['name' => 'Flared Arms'],
                        ['name' => 'Pillow Top Arms'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the chair frame.',
                    'values' => [
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Frame Finish' => [
                    'help_text' => 'Select the finish for the chair frame.',
                    'values' => [
                        ['name' => 'Brown'],
                        ['name' => 'Black'],
                        ['name' => 'Gold'],
                        ['name' => 'Gray'],
                        ['name' => 'Stainless Steel'],
                        ['name' => 'Chrome'],
                        ['name' => 'White'],
                        ['name' => 'Silver'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Walnut'],
                        ['name' => 'Blue'],
                        ['name' => 'Beige'],
                        ['name' => 'Cherry'],
                        ['name' => 'Copper'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Orange'],
                        ['name' => 'Green'],
                        ['name' => 'Yellow'],
                        ['name' => 'Red'],
                        ['name' => 'Oak'],
                        ['name' => 'Transparent'],
                        ['name' => 'Brass'],
                        ['name' => 'Bronze'],
                        ['name' => 'Pine'],
                        ['name' => 'Pink'],
                        ['name' => 'Purple'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Maple'],
                    ]
                ],
            ],
            'Ottomans and Footrests' => [
                'Shape' => [
                    'help_text' => 'Select the shape of the ottoman or footrest.',
                    'values' => [
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Round'],
                        ['name' => 'Novelty'],
                        ['name' => 'Oval'],
                        ['name' => 'Octagon'],
                        ['name' => 'Hexagon'],
                    ]
                ],
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the ottoman or footrest upholstery.',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Cotton'],
                        ['name' => 'Wool'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Chenille'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Felt'],
                        ['name' => 'Suede'],
                        ['name' => 'Acrylic'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Cotton Blend'],
                        ['name' => 'Sheepskin'],
                        ['name' => 'Jute & Sisal'],
                        ['name' => 'Canvas'],
                    ]
                ],
                'Design' => [
                    'help_text' => 'Select the design or style of the ottoman or footrest.',
                    'values' => [
                        ['name' => 'Ottoman'],
                        ['name' => 'Footstool'],
                        ['name' => 'Cocktail/Coffee Table'],
                        ['name' => 'Cube'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the ottoman or footrest offers.',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Storage'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Tray Top'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Casters'],
                        ['name' => 'Skirted'],
                        ['name' => 'Glider'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the ottoman or footrest frame.',
                    'values' => [
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wood'],
                    ]
                ],
            ],
            'Accent Chests & Cabinets' => [
                'Material' => [
                    'help_text' => 'Choose the primary material used for the chest or cabinet.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the chest or cabinet.',
                    'values' => [
                        ['name' => 'Natural Finish'],
                        ['name' => 'Cherry'],
                        ['name' => 'Walnut'],
                        ['name' => 'Gold'],
                        ['name' => 'Silver'],
                        ['name' => 'Oak'],
                        ['name' => 'Pine'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Espresso'],
                        ['name' => 'Maple'],
                    ]
                ],
                'Number of Drawers' => [
                    'help_text' => 'Select the number of drawers in the chest or cabinet.',
                    'values' => [
                        ['name' => '1 Drawer'],
                        ['name' => '2 Drawers'],
                        ['name' => '3 Drawers'],
                        ['name' => '4 Drawers'],
                        ['name' => '5 Drawers'],
                        ['name' => '6 Drawers'],
                        ['name' => '7 Drawers'],
                        ['name' => '8 Drawers'],
                        ['name' => '9 Or More Drawers'],
                    ]
                ],
                'Shape' => [
                    'help_text' => 'Select the shape of the chest or cabinet.',
                    'values' => [
                        ['name' => 'Rectangle'],
                        ['name' => 'Round'],
                        ['name' => 'Square'],
                        ['name' => 'Semicircle'],
                        ['name' => 'Novelty'],
                        ['name' => 'Oval'],
                        ['name' => 'Bombe'],
                        ['name' => 'Triangle'],
                    ]
                ],
                'Number of Doors' => [
                    'help_text' => 'Select the number of doors in the chest or cabinet.',
                    'values' => [
                        ['name' => '1 Door'],
                        ['name' => '2 Doors'],
                        ['name' => '3 Doors'],
                        ['name' => '4 Doors'],
                        ['name' => '5 Or More Doors'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the chest or cabinet offers.',
                    'values' => [
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Adjustable Shelves'],
                        ['name' => 'Hand-Painted'],
                        ['name' => 'Glass Doors'],
                        ['name' => 'Apothecary Style'],
                    ]
                ],
            ],
            'Media Storage' => [
                'Type' => [
                    'help_text' => 'Select the type of media storage unit.',
                    'values' => [
                        ['name' => 'TV Stand'],
                        ['name' => 'Entertainment Center'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common sizes for media storage units.',
                    'values' => [
                        ['name' => '32 Inch'],
                        ['name' => '40 Inch'],
                        ['name' => '42 Inch'],
                        ['name' => '43 Inch'],
                        ['name' => '48 Inch'],
                        ['name' => '50 Inch'],
                        ['name' => '55 Inch'],
                        ['name' => '60 Inch'],
                        ['name' => '65 Inch'],
                        ['name' => '70 Inch'],
                        ['name' => '72 Inch'],
                        ['name' => '75 Inch'],
                        ['name' => '80 Inch'],
                        ['name' => '86 Inch'],
                        ['name' => '90 Inch'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the media storage unit offers.',
                    'values' => [
                        ['name' => 'Media Storage'],
                        ['name' => 'Corner Unit'],
                        ['name' => 'Doors'],
                        ['name' => 'Drawers'],
                        ['name' => 'Adjustable Shelves'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Cable Management'],
                        ['name' => 'Floating'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Swivel Base'],
                        ['name' => 'Casters'],
                        ['name' => 'Flat-Screen Mount'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the frame of the media storage unit.',
                    'values' => [
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Shelving Material' => [
                    'help_text' => 'Choose the material used for the shelves of the media storage unit.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                    ]
                ],
            ],
            'Bookcases' => [
                'Finish' => [
                    'help_text' => 'Choose the finish for the bookcase.',
                    'values' => [
                        ['name' => 'Natural Finish'],
                        ['name' => 'Cherry'],
                        ['name' => 'Walnut'],
                        ['name' => 'Espresso'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Gold'],
                        ['name' => 'Pine'],
                        ['name' => 'Silver'],
                        ['name' => 'Maple'],
                        ['name' => 'Bronze'],
                        ['name' => 'Copper'],
                        ['name' => 'Transparent'],
                    ]
                ],
                'Type' => [
                    'help_text' => 'Select the type of bookcase.',
                    'values' => [
                        ['name' => 'Standard'],
                        ['name' => 'Barrister'],
                        ['name' => 'Cube'],
                        ['name' => 'Corner'],
                        ['name' => 'Wall Unit'],
                        ['name' => 'Novelty'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the bookcase frame.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the bookcase.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Medium Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Number of Shelves' => [
                    'help_text' => 'Select the number of shelves in the bookcase.',
                    'values' => [
                        ['name' => '1 Shelf'],
                        ['name' => '2 Shelves'],
                        ['name' => '3 Shelves'],
                        ['name' => '4 Shelves'],
                        ['name' => '5 Shelves'],
                        ['name' => '6 Shelves'],
                        ['name' => '7 Shelves'],
                        ['name' => '8 Shelves'],
                        ['name' => '9 Shelves'],
                        ['name' => '10 Shelves'],
                        ['name' => '11 Shelves'],
                        ['name' => '12 Or More Shelves'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the bookcase offers.',
                    'values' => [
                        ['name' => 'Adjustable Shelves'],
                        ['name' => 'Doors'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Drawers'],
                        ['name' => 'Modular'],
                        ['name' => 'Folding'],
                        ['name' => 'Asymmetrical'],
                        ['name' => 'Rolling'],
                        ['name' => 'Oversized'],
                    ]
                ],
                'Back Panel' => [
                    'help_text' => 'Select the type of back panel for the bookcase.',
                    'values' => [
                        ['name' => 'Closed'],
                        ['name' => 'Open'],
                    ]
                ],
                'Shelf Material' => [
                    'help_text' => 'Choose the material used for the shelves.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Glass'],
                    ]
                ],
                'Orientation' => [
                    'help_text' => 'Select the orientation of the bookcase.',
                    'values' => [
                        ['name' => 'Vertical'],
                        ['name' => 'Horizontal'],
                        ['name' => 'Dual Orientation'],
                    ]
                ],
            ],
            'Beds' => [
                'Bed Size' => [
                    'help_text' => 'Select the size of the bed.',
                    'values' => [
                        ['name' => 'Queen'],
                        ['name' => 'King'],
                        ['name' => 'Full & Double'],
                        ['name' => 'Twin'],
                        ['name' => 'California King'],
                        ['name' => 'Twin XL'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the bed frame.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Metal'],
                        ['name' => 'Upholstered'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Headboard Design' => [
                    'help_text' => 'Select the design of the bed headboard.',
                    'values' => [
                        ['name' => 'Panel'],
                        ['name' => 'Wingback'],
                        ['name' => 'Bookcase/Storage'],
                    ]
                ],
                'Upholstery Material' => [
                    'help_text' => 'Choose the material used for the bed upholstery.',
                    'values' => [
                        ['name' => 'Faux Leather'],
                        ['name' => 'Velvet'],
                        ['name' => 'Linen'],
                        ['name' => 'Leather'],
                        ['name' => 'Acrylic'],
                        ['name' => 'Flannel'],
                        ['name' => 'Polyester'],
                        ['name' => 'Chenille'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the bed offers.',
                    'values' => [
                        ['name' => 'Upholstered Headboard'],
                        ['name' => 'Storage'],
                        ['name' => 'Tufted Headboard'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Trundle Bed'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Pop Up Trundle'],
                        ['name' => 'Mattress Included'],
                        ['name' => 'Box Spring Required'],
                    ]
                ],
                'Overall Height' => [
                    'help_text' => 'Select the overall height range of the bed.',
                    'values' => [
                        ['name' => '15 To 19 Inches'],
                        ['name' => '20 To 24 Inches'],
                        ['name' => '25 To 29 Inches'],
                        ['name' => '30 To 34 Inches'],
                        ['name' => '35 To 39 Inches'],
                        ['name' => '40 To 44 Inches'],
                        ['name' => '45 To 49 Inches'],
                        ['name' => '50 To 54 Inches'],
                        ['name' => '55 To 59 Inches'],
                        ['name' => '60 To 64 Inches'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the bed.',
                    'values' => [
                        ['name' => 'Walnut'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Cherry'],
                        ['name' => 'Gold'],
                        ['name' => 'Oak'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Espresso'],
                        ['name' => 'Pine'],
                        ['name' => 'Maple'],
                        ['name' => 'Gray'],
                        ['name' => 'White'],
                        ['name' => 'Black'],
                        ['name' => 'Brown'],
                        ['name' => 'Blue'],
                        ['name' => 'Chrome'],
                    ]
                ],
                'Material' => [
                    'help_text' => 'Choose the primary material used for the bed.',
                    'values' => [
                        ['name' => 'Fabric'],
                        ['name' => 'Leather'],
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the bed.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Medium Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
            ],
            'Dressers & Chests' => [
                'Popular Sizes' => [
                    'help_text' => 'Select the most common dresser and chest sizes.',
                    'values' => [
                        ['name' => '30 Inch'],
                        ['name' => '40 Inch'],
                        ['name' => '42 Inch'],
                        ['name' => '48 Inch'],
                        ['name' => '50 Inch'],
                        ['name' => '60 Inch'],
                    ]
                ],
                'Design' => [
                    'help_text' => 'Select the design or style of the dresser or chest.',
                    'values' => [
                        ['name' => 'Dresser (Horizontal)'],
                        ['name' => 'Chest (Vertical)'],
                        ['name' => 'Bachelors Chest'],
                        ['name' => 'Lingerie Chest'],
                        ['name' => 'Combo Dresser'],
                    ]
                ],
                'Number of Drawers' => [
                    'help_text' => 'Select the number of drawers in the dresser or chest.',
                    'values' => [
                        ['name' => '1 Drawer'],
                        ['name' => '2 Drawers'],
                        ['name' => '3 Drawers'],
                        ['name' => '4 Drawers'],
                        ['name' => '5 Drawers'],
                        ['name' => '6 Drawers'],
                        ['name' => '7 Drawers'],
                        ['name' => '8 Drawers'],
                        ['name' => '9 Or More Drawers'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the dresser or chest.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Medium Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Material' => [
                    'help_text' => 'Choose the primary material used for the dresser or chest.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Reclaimed Wood'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the dresser or chest.',
                    'values' => [
                        ['name' => 'Oak'],
                        ['name' => 'Cherry'],
                        ['name' => 'Walnut'],
                        ['name' => 'Espresso'],
                        ['name' => 'Pine'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Silver'],
                        ['name' => 'Maple'],
                        ['name' => 'Gold'],
                        ['name' => 'Unfinished'],
                    ]
                ],
                'Hardware Finish' => [
                    'help_text' => 'Select the finish for the dresser or chest hardware.',
                    'values' => [
                        ['name' => 'White'],
                        ['name' => 'Gray'],
                        ['name' => 'Silver'],
                        ['name' => 'Black'],
                        ['name' => 'Brown'],
                        ['name' => 'Gold'],
                        ['name' => 'Pewter'],
                        ['name' => 'Bronze'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the dresser or chest offers.',
                    'values' => [
                        ['name' => 'Distressed Finish'],
                        ['name' => 'With Mirror'],
                        ['name' => 'Fully-Assembled'],
                        ['name' => 'Felt-Lined Top Drawer'],
                        ['name' => 'Double'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Soft-Close Drawers'],
                        ['name' => 'Cedar-Lined Drawers'],
                        ['name' => 'Hand-Painted'],
                        ['name' => 'Two-Tone'],
                        ['name' => 'Media Storage'],
                        ['name' => 'Charging Station'],
                        ['name' => 'Marble-Top'],
                        ['name' => 'Bow-Front'],
                        ['name' => 'Cord Control'],
                    ]
                ],
            ],
            'Nightstands & Bedside Tables' => [
                'Finish' => [
                    'help_text' => 'Choose the finish for the nightstand or bedside table.',
                    'values' => [
                        ['name' => 'White'],
                        ['name' => 'Gray'],
                        ['name' => 'Brown'],
                        ['name' => 'Black'],
                        ['name' => 'Cherry'],
                        ['name' => 'Oak'],
                        ['name' => 'Walnut'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Silver'],
                        ['name' => 'Beige'],
                        ['name' => 'Blue'],
                        ['name' => 'Espresso'],
                        ['name' => 'Pine'],
                        ['name' => 'Gold'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Pink'],
                        ['name' => 'Turquoise'],
                        ['name' => 'Red'],
                        ['name' => 'Multicolor'],
                        ['name' => 'Maple'],
                        ['name' => 'Yellow'],
                        ['name' => 'Green'],
                        ['name' => 'Orange'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Black & White'],
                        ['name' => 'Birch'],
                        ['name' => 'Purple'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the nightstand or bedside table frame.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Number of Drawers' => [
                    'help_text' => 'Select the number of drawers in the nightstand or bedside table.',
                    'values' => [
                        ['name' => '1 Drawer'],
                        ['name' => '2 Drawers'],
                        ['name' => '3 Drawers'],
                        ['name' => '4 Drawers'],
                        ['name' => '5 Drawers'],
                    ]
                ],
                'Top Material' => [
                    'help_text' => 'Choose the material used for the top of the nightstand or bedside table.',
                    'values' => [
                        ['name' => 'Marble'],
                        ['name' => 'Glass'],
                        ['name' => 'Stone'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the nightstand or bedside table.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the nightstand or bedside table offers.',
                    'values' => [
                        ['name' => 'Shelving'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Charging Station'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Pull-Out Tray'],
                        ['name' => 'Floating'],
                        ['name' => 'Hidden Drawer'],
                    ]
                ],
            ],
            'Headboards' => [
                'Size' => [
                    'help_text' => 'Select the size of the headboard.',
                    'values' => [
                        ['name' => 'Large'],
                        ['name' => 'More Options'],
                        ['name' => 'Queen'],
                        ['name' => 'Twin'],
                        ['name' => 'King'],
                        ['name' => 'Full & Double'],
                        ['name' => 'California King'],
                    ]
                ],
                'Material' => [
                    'help_text' => 'Choose the primary material used for the headboard.',
                    'values' => [
                        ['name' => 'Fabric'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the headboard.',
                    'values' => [
                        ['name' => 'Walnut'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Espresso'],
                        ['name' => 'Gold'],
                        ['name' => 'Pine'],
                        ['name' => 'Silver'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Bronze'],
                        ['name' => 'Oak'],
                        ['name' => 'Cherry'],
                        ['name' => 'Iron'],
                        ['name' => 'Copper'],
                        ['name' => 'Maple'],
                    ]
                ],
                'Type' => [
                    'help_text' => 'Select the type of headboard.',
                    'values' => [
                        ['name' => 'Headboard Only'],
                    ]
                ],
                'Design' => [
                    'help_text' => 'Select the design or style of the headboard.',
                    'values' => [
                        ['name' => 'Panel'],
                        ['name' => 'Curved'],
                        ['name' => 'Wingback'],
                        ['name' => 'Slat'],
                        ['name' => 'Bookcase/Storage'],
                        ['name' => 'Lattice'],
                        ['name' => 'Sleigh'],
                    ]
                ],
                'Upholstery' => [
                    'help_text' => 'Choose the upholstery material for the headboard.',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Cotton Blend'],
                        ['name' => 'Cotton'],
                        ['name' => 'Velvet'],
                        ['name' => 'Linen'],
                        ['name' => 'Synthetic'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Suede'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Leather'],
                    ]
                ],
                'Height' => [
                    'help_text' => 'Select the height or height range of the headboard.',
                    'values' => [
                        ['name' => 'Tall'],
                        ['name' => 'More Options'],
                        ['name' => '15 To 19 Inches'],
                        ['name' => '20 To 24 Inches'],
                        ['name' => '25 To 29 Inches'],
                        ['name' => '30 To 34 Inches'],
                        ['name' => '35 To 39 Inches'],
                        ['name' => '40 To 44 Inches'],
                        ['name' => '45 To 49 Inches'],
                        ['name' => '50 To 54 Inches'],
                        ['name' => '55 To 59 Inches'],
                        ['name' => '60 To 64 Inches'],
                        ['name' => '65 To 69 Inches'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the headboard offers.',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Padded'],
                        ['name' => 'Adjustable'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Wingback'],
                        ['name' => 'Woven'],
                        ['name' => 'Wall-Mounted'],
                        ['name' => 'Hand-Painted'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the headboard.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Medium Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
            ],
            'Benches' => [
                'Upholstery Color' => [
                    'help_text' => 'Select the color of the bench upholstery.',
                    'values' => [
                        ['name' => 'Beige'],
                        ['name' => 'Gray'],
                        ['name' => 'White'],
                        ['name' => 'Black'],
                        ['name' => 'Brown'],
                        ['name' => 'Blue'],
                        ['name' => 'Green'],
                        ['name' => 'Gold'],
                        ['name' => 'Silver'],
                        ['name' => 'Pink'],
                        ['name' => 'Turquoise'],
                        ['name' => 'Orange'],
                        ['name' => 'Purple'],
                    ]
                ],
                'Bench Width' => [
                    'help_text' => 'Select the width range of the bench.',
                    'values' => [
                        ['name' => '15 To 19 Inches'],
                        ['name' => '20 To 24 Inches'],
                        ['name' => '25 To 29 Inches'],
                        ['name' => '30 To 34 Inches'],
                        ['name' => '35 To 39 Inches'],
                        ['name' => '40 To 44 Inches'],
                        ['name' => '45 To 49 Inches'],
                        ['name' => '50 To 54 Inches'],
                        ['name' => '55 To 59 Inches'],
                        ['name' => '60 To 64 Inches'],
                        ['name' => '65 To 69 Inches'],
                        ['name' => '70 To 79 Inches'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the bench frame.',
                    'values' => [
                        ['name' => 'Fabric'],
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Upholstery Fabric' => [
                    'help_text' => 'Select the fabric used for the bench upholstery.',
                    'values' => [
                        ['name' => 'Velvet'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Acrylic'],
                    ]
                ],
                'Storage Type' => [
                    'help_text' => 'Select the type of storage the bench offers.',
                    'values' => [
                        ['name' => 'Lift Top'],
                        ['name' => 'Shelf'],
                        ['name' => 'Cubby'],
                        ['name' => 'Drawer'],
                        ['name' => 'Shoe Storage'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the bench offers.',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'With Arms'],
                        ['name' => 'Storage'],
                        ['name' => 'With Back'],
                    ]
                ],
            ],
            'Buffets & Sideboards' => [
                'Top Material' => [
                    'help_text' => 'Choose the material used for the top of the buffet or sideboard.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Glass'],
                        ['name' => 'Mirror'],
                        ['name' => 'Stone'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Concrete'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the base of the buffet or sideboard.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the buffet or sideboard.',
                    'values' => [
                        ['name' => 'Walnut'],
                        ['name' => 'Gold'],
                        ['name' => 'Cherry'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Oak'],
                        ['name' => 'Silver'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Pine'],
                        ['name' => 'Espresso'],
                        ['name' => 'Maple'],
                        ['name' => 'Transparent'],
                        ['name' => 'Reclaimed Wood'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the buffet or sideboard.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Medium Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Storage' => [
                    'help_text' => 'Select the types of storage available in the buffet or sideboard.',
                    'values' => [
                        ['name' => 'Cabinets'],
                        ['name' => 'Drawers'],
                        ['name' => 'Display Case'],
                        ['name' => 'Adjustable Shelves'],
                        ['name' => 'Wine Bottle Storage'],
                        ['name' => 'Open Storage'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the buffet or sideboard offers.',
                    'values' => [
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Lacquered'],
                        ['name' => 'Glass Doors'],
                        ['name' => 'Curved'],
                        ['name' => 'Sliding Doors'],
                        ['name' => 'Hutch'],
                        ['name' => 'Felt-Lined Drawers'],
                        ['name' => 'Locking'],
                        ['name' => 'Drop Leaf/Expandable'],
                    ]
                ],
            ],
            'Islands and Carts' => [
                'Counter Material' => [
                    'help_text' => 'Choose the material used for the island or cart countertop.',
                    'values' => [
                        ['name' => 'Butcher Block'],
                        ['name' => 'Wood'],
                        ['name' => 'Granite'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Stone'],
                    ]
                ],
                'Length' => [
                    'help_text' => 'Select the length range of the island or cart.',
                    'values' => [
                        ['name' => '15 To 19 Inches'],
                        ['name' => '20 To 24 Inches'],
                        ['name' => '25 To 29 Inches'],
                        ['name' => '30 To 34 Inches'],
                        ['name' => '35 To 39 Inches'],
                        ['name' => '40 To 44 Inches'],
                        ['name' => '45 To 49 Inches'],
                        ['name' => '50 To 54 Inches'],
                        ['name' => '60 To 64 Inches'],
                        ['name' => '70 To 79 Inches'],
                    ]
                ],
                'Type' => [
                    'help_text' => 'Select the type of island or cart.',
                    'values' => [
                        ['name' => 'Prep Table'],
                        ['name' => 'Kitchen Island'],
                        ['name' => 'Kitchen Cart'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the island or cart.',
                    'values' => [
                        ['name' => 'Oak'],
                        ['name' => 'Maple'],
                        ['name' => 'White'],
                        ['name' => 'Black'],
                        ['name' => 'Gray'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Brown'],
                        ['name' => 'Cherry'],
                        ['name' => 'Green'],
                        ['name' => 'Red'],
                        ['name' => 'Blue'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Silver'],
                        ['name' => 'Walnut'],
                        ['name' => 'Turquoise'],
                        ['name' => 'Yellow'],
                        ['name' => 'Espresso'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Beige'],
                        ['name' => 'Pine'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the island or cart offers.',
                    'values' => [
                        ['name' => 'Open Storage'],
                        ['name' => 'Drawers'],
                        ['name' => 'Cabinets'],
                        ['name' => 'Casters/Wheels'],
                        ['name' => 'Locking Casters'],
                        ['name' => 'Drop Leaf'],
                        ['name' => 'Towel Bar'],
                        ['name' => 'Adjustable Shelving'],
                        ['name' => 'Folding'],
                        ['name' => 'Wine Storage'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the base of the island or cart.',
                    'values' => [
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wood'],
                    ]
                ],
            ],
            'China Cabinets and Hutches' => [
                'Finish' => [
                    'help_text' => 'Choose the finish for the china cabinet or hutch.',
                    'values' => [
                        ['name' => 'Black'],
                        ['name' => 'Brown'],
                        ['name' => 'White'],
                        ['name' => 'Cherry'],
                        ['name' => 'Gray'],
                        ['name' => 'Oak'],
                        ['name' => 'Gold'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Green'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Blue'],
                        ['name' => 'Brass'],
                        ['name' => 'Red'],
                        ['name' => 'Silver'],
                        ['name' => 'Espresso'],
                        ['name' => 'Iron'],
                        ['name' => 'Turquoise'],
                        ['name' => 'Walnut'],
                        ['name' => 'Mirrored'],
                        ['name' => 'Bronze'],
                        ['name' => 'Yellow'],
                        ['name' => 'Beige'],
                        ['name' => 'Nickel'],
                        ['name' => 'Orange'],
                        ['name' => 'Pine'],
                        ['name' => 'Unfinished'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the china cabinet or hutch offers.',
                    'values' => [
                        ['name' => 'Glass Doors'],
                        ['name' => 'With Hutch'],
                        ['name' => 'Corner Unit'],
                        ['name' => 'Lighted'],
                        ['name' => 'Mirrored Back'],
                        ['name' => 'Locking'],
                        ['name' => 'Sliding Doors'],
                        ['name' => 'Hand-Painted'],
                    ]
                ],
                'Type' => [
                    'help_text' => 'Select the type of china cabinet or hutch.',
                    'values' => [
                        ['name' => 'Curio Cabinet'],
                        ['name' => 'Hutch Only'],
                        ['name' => 'China Cabinet'],
                    ]
                ],
                'Frame Material' => [
                    'help_text' => 'Choose the material used for the frame.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Storage' => [
                    'help_text' => 'Select the types of storage available.',
                    'values' => [
                        ['name' => 'Drawers'],
                        ['name' => 'Adjustable Shelves'],
                        ['name' => 'Open Storage'],
                        ['name' => 'Wine Bottle Storage'],
                    ]
                ],
                'Shelf Material' => [
                    'help_text' => 'Choose the material used for the shelves.',
                    'values' => [
                        ['name' => 'Glass'],
                        ['name' => 'Wood'],
                        ['name' => 'Metal'],
                    ]
                ],
                'Number of Shelves' => [
                    'help_text' => 'Select the number of shelves in the cabinet or hutch.',
                    'values' => [
                        ['name' => '1 Shelf'],
                        ['name' => '2 Shelves'],
                        ['name' => '3 Shelves'],
                        ['name' => '4 Shelves'],
                        ['name' => '5 Shelves'],
                        ['name' => '6 Shelves'],
                        ['name' => '7 Shelves'],
                        ['name' => '8 Shelves'],
                    ]
                ],
            ],
            'Dining Chairs' => [
                'Material' => [
                    'help_text' => 'Choose the primary material used for the dining chair.',
                    'values' => [
                        ['name' => 'Fabric'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Leather'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Chair Design' => [
                    'help_text' => 'Select the design or style of the dining chair.',
                    'values' => [
                        ['name' => 'Side Chair'],
                        ['name' => 'Armchair'],
                        ['name' => 'Wingback Chair'],
                        ['name' => 'Sloped Arm'],
                        ['name' => 'Parsons Chair'],
                    ]
                ],
                'Quantity' => [
                    'help_text' => 'Select whether the chair is sold individually or as a set.',
                    'values' => [
                        ['name' => 'Individual'],
                        ['name' => 'Set'],
                    ]
                ],
                'Type' => [
                    'help_text' => 'Select the type of dining chair.',
                    'values' => [
                        ['name' => 'Casual'],
                        ['name' => 'Formal'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the dining chair offers.',
                    'values' => [
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Stacking'],
                        ['name' => 'Tufted'],
                        ['name' => 'Swivel'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Arm Pads'],
                        ['name' => 'Transparent Finish'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'More Options'],
                        ['name' => 'Ergonomic'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Wheels'],
                        ['name' => 'Two-Tone'],
                    ]
                ],
                'Back Design' => [
                    'help_text' => 'Select the design of the chair back.',
                    'values' => [
                        ['name' => 'Solid'],
                        ['name' => 'Open'],
                        ['name' => 'Decorative'],
                        ['name' => 'Cane'],
                        ['name' => 'Slat'],
                        ['name' => 'Round'],
                        ['name' => 'Windsor'],
                        ['name' => 'Oval'],
                        ['name' => 'Wishbone'],
                        ['name' => 'Ladder'],
                        ['name' => 'Cross'],
                    ]
                ],
                'Back Height' => [
                    'help_text' => 'Select the back height of the dining chair.',
                    'values' => [
                        ['name' => 'Mid Back'],
                        ['name' => 'High Back'],
                        ['name' => 'Low Back'],
                    ]
                ],
            ],
            'Dining Tables' => [
                'Shape' => [
                    'help_text' => 'Select the shape of the dining table.',
                    'values' => [
                        ['name' => 'Rectangle'],
                        ['name' => 'Round'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Free-Form'],
                        ['name' => 'Octagon'],
                    ]
                ],
                'Seating Capacity' => [
                    'help_text' => 'Select the seating capacity of the dining table.',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Large'],
                        ['name' => 'More Options'],
                        ['name' => 'Seats 1'],
                        ['name' => 'Seats 2'],
                        ['name' => 'Seats 3'],
                        ['name' => 'Seats 4'],
                        ['name' => 'Seats 6'],
                        ['name' => 'Seats 8'],
                        ['name' => 'Seats 10'],
                        ['name' => 'Seats 12'],
                    ]
                ],
                'Table Height' => [
                    'help_text' => 'Select the height of the dining table.',
                    'values' => [
                        ['name' => 'Standard Height'],
                        ['name' => 'Counter Height'],
                        ['name' => 'Adjustable'],
                    ]
                ],
                'Top Material' => [
                    'help_text' => 'Choose the material used for the table top.',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Marble'],
                        ['name' => 'Metal'],
                        ['name' => 'Faux Stone'],
                        ['name' => 'Concrete'],
                        ['name' => 'Stone'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Mirror'],
                        ['name' => 'Zinc'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the table base.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Concrete'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the dining table.',
                    'values' => [
                        ['name' => 'Acacia'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Walnut'],
                        ['name' => 'Oak'],
                        ['name' => 'Gold'],
                        ['name' => 'Cherry'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Silver'],
                        ['name' => 'Espresso'],
                        ['name' => 'Transparent'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Bronze'],
                        ['name' => 'Maple'],
                        ['name' => 'Pine'],
                        ['name' => 'Copper'],
                        ['name' => 'Iron'],
                        ['name' => 'Birch'],
                        ['name' => 'Mirrored'],
                    ]
                ],
                'Wood Tone' => [
                    'help_text' => 'Select the wood tone for the dining table.',
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Base Design' => [
                    'help_text' => 'Select the design of the table base.',
                    'values' => [
                        ['name' => '4 Legs'],
                        ['name' => 'Pedestal'],
                        ['name' => 'Trestle'],
                        ['name' => 'Double Pedestal'],
                        ['name' => 'Tulip'],
                        ['name' => 'Novelty'],
                        ['name' => 'Waterfall'],
                        ['name' => 'Tripod'],
                        ['name' => 'Drum'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common dining table sizes.',
                    'values' => [
                        ['name' => '30 Inch'],
                        ['name' => '36 Inch'],
                        ['name' => '42 Inch'],
                        ['name' => '48 Inch'],
                        ['name' => '54 Inch'],
                        ['name' => '60 Inch'],
                        ['name' => '72 Inch'],
                        ['name' => '78 Inch'],
                        ['name' => '84 Inch'],
                        ['name' => '94 Inch'],
                        ['name' => '96 Inch'],
                        ['name' => '108 Inch'],
                        ['name' => '120 Inch'],
                    ]
                ],
                'Casual/Formal' => [
                    'help_text' => 'Select whether the table is casual or formal.',
                    'values' => [
                        ['name' => 'Casual'],
                        ['name' => 'Formal'],
                    ]
                ],
                'Table Features' => [
                    'help_text' => 'Select any special features the dining table offers.',
                    'values' => [
                        ['name' => 'Extension'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Butterfly Leaf'],
                        ['name' => 'Drop Leaf'],
                        ['name' => 'Shelves'],
                        ['name' => 'Wheels'],
                    ]
                ],
            ],
            'Bar Stools & Counter Stools' => [
                'Seat Height' => [
                    'help_text' => 'Select the seat height for the stool.',
                    'values' => [
                        ['name' => 'Counter Height'],
                        ['name' => 'Bar Height'],
                        ['name' => 'Adjustable'],
                        ['name' => 'Extra-Tall'],
                        ['name' => 'Short'],
                    ]
                ],
                'Number in Set' => [
                    'help_text' => 'Select the number of stools in the set.',
                    'values' => [
                        ['name' => '2 Piece Set'],
                        ['name' => '3 Piece Set'],
                        ['name' => '4 Piece Set'],
                    ]
                ],
                'Back Height' => [
                    'help_text' => 'Select the back height of the stool.',
                    'values' => [
                        ['name' => 'High Back'],
                        ['name' => 'Low Back'],
                        ['name' => 'Backless'],
                    ]
                ],
                'Back Style' => [
                    'help_text' => 'Select the style of the stool back.',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Wingback'],
                        ['name' => 'Cross'],
                        ['name' => 'Ladder'],
                        ['name' => 'Windsor'],
                    ]
                ],
                'Seat Material' => [
                    'help_text' => 'Choose the material used for the seat.',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                        ['name' => 'Fabric'],
                        ['name' => 'Leather'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Plastic'],
                        ['name' => 'Velvet'],
                    ]
                ],
                'Seat Type' => [
                    'help_text' => 'Select the type of seat.',
                    'values' => [
                        ['name' => 'Square Seat'],
                        ['name' => 'Round Seat'],
                        ['name' => 'Saddle Seat'],
                        ['name' => 'Bucket Seat'],
                    ]
                ],
                'Frame Finish' => [
                    'help_text' => 'Choose the finish for the stool frame.',
                    'values' => [
                        ['name' => 'Black'],
                        ['name' => 'White'],
                        ['name' => 'Transparent'],
                        ['name' => 'Bronze'],
                        ['name' => 'Chrome'],
                        ['name' => 'Copper'],
                        ['name' => 'Gold'],
                        ['name' => 'Silver'],
                        ['name' => 'Stainless Steel'],
                        ['name' => 'Cherry'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Oak'],
                        ['name' => 'Walnut'],
                        ['name' => 'Pine'],
                        ['name' => 'Maple'],
                        ['name' => 'Natural Finish'],
                    ]
                ],
                'Stool Base' => [
                    'help_text' => 'Select the base style of the stool.',
                    'values' => [
                        ['name' => '4 Legs'],
                        ['name' => 'Pedestal'],
                    ]
                ],
                'Base Material' => [
                    'help_text' => 'Choose the material used for the base.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the stool offers.',
                    'values' => [
                        ['name' => 'Swivel'],
                        ['name' => 'Footrest'],
                        ['name' => 'Padded Seat'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Airlift'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Tufted'],
                        ['name' => 'With Arms'],
                        ['name' => 'Stackable'],
                        ['name' => 'Folding'],
                        ['name' => 'Heavy Duty'],
                    ]
                ],
                'Popular Sizes' => [
                    'help_text' => 'Select the most common stool sizes.',
                    'values' => [
                        ['name' => '24 Inch'],
                        ['name' => '25 Inch'],
                        ['name' => '26 Inch'],
                        ['name' => '29 Inch'],
                        ['name' => '30 Inch'],
                        ['name' => '32 Inch'],
                        ['name' => '33 Inch'],
                        ['name' => '34 Inch'],
                        ['name' => '36 Inch'],
                    ]
                ],
            ],
            'Bedframes' => [
                'Size' => [
                    'help_text' => 'Select the size of the bedframe.',
                    'values' => [
                        ['name' => 'Twin'],
                        ['name' => 'Queen'],
                        ['name' => 'Full & Double'],
                        ['name' => 'King'],
                        ['name' => 'California King'],
                        ['name' => 'Twin XL'],
                    ]
                ],
                'Material' => [
                    'help_text' => 'Choose the primary material used for the bedframe.',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the bedframe.',
                    'values' => [
                        ['name' => 'Gray'],
                        ['name' => 'White'],
                        ['name' => 'Black'],
                        ['name' => 'Brown'],
                        ['name' => 'Silver'],
                    ]
                ],
                'Frame Type' => [
                    'help_text' => 'Select the type of bedframe.',
                    'values' => [
                        ['name' => 'Bed Frame'],
                        ['name' => 'Trundle Unit'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the bedframe offers.',
                    'values' => [
                        ['name' => 'Adjustable'],
                    ]
                ],
            ],
            'Mattresses' => [
                'Mattress Size' => [
                    'help_text' => 'Select the size of the mattress.',
                    'values' => [
                        ['name' => 'Queen'],
                        ['name' => 'Twin'],
                        ['name' => 'Full & Double'],
                        ['name' => 'King'],
                        ['name' => 'California King'],
                        ['name' => 'Twin XL'],
                        ['name' => 'Full XL'],
                    ]
                ],
                'Mattress Type' => [
                    'help_text' => 'Select the type of mattress.',
                    'values' => [
                        ['name' => 'Gel Foam'],
                        ['name' => 'Memory Foam'],
                        ['name' => 'Pocket Coil'],
                        ['name' => 'Innerspring'],
                        ['name' => 'Latex'],
                        ['name' => 'Foundation/Box Spring'],
                        ['name' => 'Air Mattress'],
                    ]
                ],
                'Comfort Level' => [
                    'help_text' => 'Select the comfort level of the mattress.',
                    'values' => [
                        ['name' => 'Plush'],
                        ['name' => 'Firm'],
                        ['name' => 'Medium'],
                        ['name' => 'Extra Firm'],
                        ['name' => 'Ultra Plush'],
                    ]
                ],
                'Mattress Top' => [
                    'help_text' => 'Select the type of mattress top.',
                    'values' => [
                        ['name' => 'Memory Foam Top'],
                        ['name' => 'Tight Top'],
                        ['name' => 'Pillow Top'],
                        ['name' => 'Euro Top'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the mattress offers.',
                    'values' => [
                        ['name' => 'Fire-Resistant'],
                        ['name' => 'Organic'],
                        ['name' => 'Cooling'],
                        ['name' => 'Adjustable'],
                        ['name' => 'Hypoallergenic'],
                        ['name' => 'Flippable'],
                        ['name' => 'Removable Cover'],
                    ]
                ],
                'Warranty Term' => [
                    'help_text' => 'Select the warranty term for the mattress.',
                    'values' => [
                        ['name' => '10 Years'],
                        ['name' => '1 Year'],
                        ['name' => '5 Years'],
                        ['name' => '20 Years'],
                        ['name' => '25 Years'],
                    ]
                ],
                'Height' => [
                    'help_text' => 'Select the height range of the mattress.',
                    'values' => [
                        ['name' => 'Under 5 Inches'],
                        ['name' => '5 To 9 Inches'],
                        ['name' => '10 To 14 Inches'],
                        ['name' => '15 To 19 Inches'],
                    ]
                ],
            ],
            'Desks' => [
                'Popular Sizes' => [
                    'help_text' => 'Select the most common desk sizes.',
                    'values' => [
                        ['name' => '30 Inch'],
                        ['name' => '36 Inch'],
                        ['name' => '40 Inch'],
                        ['name' => '48 Inch'],
                        ['name' => '50 Inch'],
                        ['name' => '60 Inch'],
                        ['name' => '70 Inch'],
                        ['name' => '72 Inch'],
                    ]
                ],
                'Desk Type' => [
                    'help_text' => 'Select the type of desk.',
                    'values' => [
                        ['name' => 'Writing Desk'],
                        ['name' => 'Computer Desk'],
                        ['name' => 'Desk Hutch'],
                        ['name' => 'Executive Desk'],
                        ['name' => 'Standing Desk'],
                        ['name' => 'Credenza Desk'],
                        ['name' => 'Desk Shell'],
                        ['name' => 'Wall Desk'],
                        ['name' => 'Secretary Desk'],
                        ['name' => 'Reception Desk'],
                        ['name' => 'Leaning Desk'],
                    ]
                ],
                'Shape' => [
                    'help_text' => 'Select the shape of the desk.',
                    'values' => [
                        ['name' => 'Standard'],
                        ['name' => 'L-Shape'],
                        ['name' => 'Corner'],
                        ['name' => 'U-Shape'],
                    ]
                ],
                'Material' => [
                    'help_text' => 'Choose the primary material used for the desk.',
                    'values' => [
                        ['name' => 'Bamboo'],
                        ['name' => 'Glass'],
                        ['name' => 'Manufactured Wood'],
                        ['name' => 'Metal'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'help_text' => 'Choose the finish for the desk.',
                    'values' => [
                        ['name' => 'Cherry'],
                        ['name' => 'Oak'],
                        ['name' => 'Walnut'],
                        ['name' => 'Natural Finish'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Silver'],
                        ['name' => 'Maple'],
                        ['name' => 'Gold'],
                        ['name' => 'Espresso'],
                        ['name' => 'Pine'],
                        ['name' => 'Driftwood'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Bronze'],
                        ['name' => 'Iron'],
                        ['name' => 'Mirrored'],
                    ]
                ],
                'Number of Drawers' => [
                    'help_text' => 'Select the number of drawers in the desk.',
                    'values' => [
                        ['name' => '1 Drawer'],
                        ['name' => '2 Drawers'],
                        ['name' => '3 Drawers'],
                        ['name' => '4 Drawers'],
                        ['name' => '5 Drawers'],
                        ['name' => '6 Drawers'],
                        ['name' => '7 Drawers'],
                        ['name' => '8 Drawers'],
                        ['name' => '9 Or More Drawers'],
                    ]
                ],
                'Features' => [
                    'help_text' => 'Select any special features the desk offers.',
                    'values' => [
                        ['name' => 'Long'],
                        ['name' => 'Wire Management'],
                        ['name' => 'Keyboard Tray'],
                        ['name' => 'Small Scale'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Wheels'],
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Ergonomic'],
                        ['name' => 'Locking'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Desk Hutch Included'],
                        ['name' => 'Printer Storage'],
                        ['name' => 'Two Tone'],
                        ['name' => 'Bow Front'],
                        ['name' => 'Desk & Chair Set'],
                        ['name' => 'Live Edge'],
                        ['name' => 'Expandable'],
                    ]
                ],
            ],
        ];

        // Optionally, keep a commented-out reference for other subcategories here for future use.
        // $referenceSubcategories = [ ... ];

        // First, create global attributes and values
        $this->createGlobalAttributes($globalAttributes);

        // Then seed subcategory-specific attributes
        $this->seedSubcategoryAttributes($subcategoryAttributes, $globalAttributes);
    }

    private function createGlobalAttributes(array $globalAttributes)
    {
        foreach ($globalAttributes as $attributeName => $attributeData) {
            $attribute = Attribute::firstOrCreate([
                'name' => $attributeName
            ], [
                'is_variant_generator' => $attributeData['is_variant_generator'] ?? false,
                'help_text' => $attributeData['help_text'] ?? null,
                'sort_order' => $attributeData['sort_order'] ?? 0
            ]);

            // Create values for global attributes
            if (isset($attributeData['values'])) {
                collect($attributeData['values'])->each(function ($valueData) use ($attribute) {
                    AttributeValue::firstOrCreate([
                        'attribute_id' => $attribute->id,
                        'name' => $valueData['name']
                    ], [
                        'representation' => $valueData['representation'] ?? null
                    ]);
                });
            }
        }
    }

    private function seedSubcategoryAttributes(array $subcategoryAttributes, array $globalAttributes)
    {
        foreach ($subcategoryAttributes as $subcategoryName => $attributes) {
            $subcategory = Subcategory::where('name', $subcategoryName)->first();

            if (!$subcategory) {
                continue;
            }

            foreach ($attributes as $attributeName => $attributeConfig) {
                // Handle reused global attributes
                if ($attributeConfig === true) {
                    $attribute = Attribute::where('name', $attributeName)->first();

                    if ($attribute) {
                        // Attach attribute to subcategory
                        $subcategory->attributes()->syncWithoutDetaching($attribute);

                        // Attach all values of this global attribute to the subcategory
                        $globalAttributeValues = AttributeValue::where('attribute_id', $attribute->id)->get();
                        foreach ($globalAttributeValues as $value) {
                            DB::table('subcategory_attribute_values')->updateOrInsert(
                                [
                                    'subcategory_id' => $subcategory->id,
                                    'attribute_id' => $attribute->id,
                                    'attribute_value_id' => $value->id
                                ],
                                [
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]
                            );
                        }
                    }
                    continue;
                }

                // Handle overridden or new attributes
                $attribute = Attribute::firstOrCreate([
                    'name' => $attributeName
                ], [
                    'is_variant_generator' => $attributeConfig['is_variant_generator'] ?? false,
                    'help_text' => $attributeConfig['help_text'] ?? null,
                    'sort_order' => $attributeConfig['sort_order'] ?? 0
                ]);

                // Attach attribute to subcategory
                $subcategory->attributes()->syncWithoutDetaching($attribute);

                // Create or use values
                if (isset($attributeConfig['values'])) {
                    foreach ($attributeConfig['values'] as $valueData) {
                        $value = AttributeValue::firstOrCreate(
                            [
                                'attribute_id' => $attribute->id,
                                'name' => $valueData['name']
                            ],
                            [
                                'representation' => $valueData['representation'] ?? null
                            ]
                        );

                        // Insert into the subcategory_attribute_values table directly
                        DB::table('subcategory_attribute_values')->updateOrInsert(
                            [
                                'subcategory_id' => $subcategory->id,
                                'attribute_id' => $attribute->id,
                                'attribute_value_id' => $value->id
                            ],
                            [
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }
                }
            }
        }
    }
}
