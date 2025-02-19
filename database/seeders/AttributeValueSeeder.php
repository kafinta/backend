<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subcategory;
use App\Models\Attribute;
use App\Models\AttributeValue;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define global attributes that can be reused
        $globalAttributes = [
            'Color' => [
                'type' => 'select',
                'is_variant_generator' => true,
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
                    ['name' => 'Black & White', ],
                    ['name' => 'Multicolor',],
                ]
            ],
            'Style' => [
                'type' => 'select',
                'is_variant_generator' => false,
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
                    ['name' => 'Tropical'],
                ]
            ],
            'Assembly' => [
                'type' => 'select',
                'is_variant_generator' => false,
                'values' => [
                    ['name' => 'Fully Assembled'],
                    ['name' => 'Requires Assembly'],
                ]
            ],
            'Pattern' => [
                'type' => 'select',
                'values' => [
                    ['name' => 'Solid Color'],
                    ['name' => 'Nature & Floral'],
                    ['name' => 'Novelty'],
                    ['name' => 'Animal Print'],
                    ['name' => 'Geometric'],
                    ['name' => 'Chevron'],
                    ['name' => 'Striped'],
                    ['name' => 'Moroccan'],
                    ['name' => 'Abstract'],
                ]
            ],
            'Frame Material' => [
                'type' => 'select',
                'values' => [
                    ['name' => 'Metal'],
                    ['name' => 'Wood'],
                    ['name' => 'Manufactured Wood'],
                    ['name' => 'Plastic & Acrylic'],
                    ['name' => 'Wicker & Rattan'],
                ]
            ],
            'Back Height' => [
                'type' => 'select',
                'values' => [
                    ['name' => 'Low Back'],
                    ['name' => 'High Back'],
                ]
            ],
            'Number in Set' => [
                'type' => 'select',
                'values' => [
                    ['name' => '2 Piece Set'],
                    ['name' => '3 Piece Set'],
                    ['name' => '4 Piece Set'],
                    ['name' => '5 Piece Set'],
                    ['name' => '6 Piece Set'],
                    ['name' => '7 Piece Set'],
                    ['name' => '12 or More Piece Set'],
                ]
            ],
            'Upholstery Material' => [
                'type' => 'select',
                'values' => [
                    ['name' => 'Polyester'],
                    ['name' => 'Velvet'],
                    ['name' => 'Leather'],
                    ['name' => 'Faux Leather'],
                    ['name' => 'Linen'],
                    ['name' => 'Cotton'],
                    ['name' => 'Canvas'],
                    ['name' => 'Chenile'],
                    ['name' => 'Suede'],
                    ['name' => 'Vinyl'],
                    ['name' => 'Cowhide'],
                    ['name' => 'Silk'],
                    ['name' => 'Cowhide'],
                    ['name' => 'Synthetic'],
                    ['name' => 'Acrylic'],
                    ['name' => 'Faux Fur'],
                    ['name' => 'Felt'],
                    ['name' => 'Jute & Sisal'],
                ]
            ],
        ];

        // Subcategory-specific attribute configurations
        $subcategoryAttributes = [
            'Sofas and Couches' => [
                'Style' => true,  // Use global attribute
                'Color' => true,
                'Pattern' => true,
                'Assembly' => true,
                'Frame Material' => true,
                'Back Type' => true,
                'Upholstery Material' => true,
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Modular'],
                        ['name' => 'Reclining'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Storage'],
                        ['name' => 'Small-Scale'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Extra Long'],
                        ['name' => 'Skirted'],
                        ['name' => 'Ottoman Included'],
                        ['name' => 'Pillows Included'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Stain Resistant'],
                        ['name' => 'Coils/Springs'],
                        ['name' => 'Distressed Leather'],
                        ['name' => 'Wheels'],
                        ['name' => '8-Way Hand Tied'],
                    ]
                ],
                'Cushion Fill' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Feather & Down'],
                        ['name' => 'Polyester'],
                        ['name' => 'Memory Foam'],
                        ['name' => 'Down Alternative'],
                    ]
                ],
                'Arm Style' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Armless'],
                        ['name' => 'One Arm'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Pillow Top Arms'],
                        ['name' => 'Sloped Arms'],
                        ['name' => 'Flared Arms'],
                        ['name' => 'Tuxedo'],
                    ]
                ],
                'Design' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Chesterfield'],
                        ['name' => 'Curved'],
                        ['name' => 'Standard'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => '5 Feet'],
                        ['name' => '6 Feet'],
                        ['name' => '7 Feet'],
                        ['name' => '8 Feet'],
                        ['name' => '9 Feet'],
                        ['name' => '10 Feet'],
                    ]
                ],
                'Seating Capacity' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Seats 2'],
                        ['name' => 'Seats 3'],
                        ['name' => 'Seats 4'],
                        ['name' => 'Seats 5'],
                        ['name' => 'Seats 6'],
                        ['name' => 'Seats 7'],
                        ['name' => 'Seats 8'],
                    ]
                ],
            ],
            'Sectional Sofas' => [
                'Style' => true,  // Use global attribute
                'Color' => true,
                'Pattern' => true,
                'Assembly' => true,
                'Frame Material' => true,
                'Back Height' => true,
                'Number in Set' => true,
                'Upholstery Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Cotton'],
                        ['name' => 'Canvas'],
                        ['name' => 'Chenile'],
                        ['name' => 'Suede'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Silk'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Synthetic'],
                        ['name' => 'Acrylic'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Felt'],
                        ['name' => 'Jute & Sisal'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Modular'],
                        ['name' => 'Reclining'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Storage'],
                        ['name' => 'Small-Scale'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Extra Long'],
                        ['name' => 'Skirted'],
                        ['name' => 'Ottoman Included'],
                        ['name' => 'Pillows Included'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Stain Resistant'],
                        ['name' => 'Coils/Springs'],
                        ['name' => 'Distressed Leather'],
                        ['name' => 'Wheels'],
                        ['name' => '8-Way Hand Tied'],
                    ]
                ],
                'Cushion Fill' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Feather & Down'],
                        ['name' => 'Polyester'],
                        ['name' => 'Memory Foam'],
                        ['name' => 'Down Alternative'],
                    ]
                ],
                'Arm Style' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Armless'],
                        ['name' => 'One Arm'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Pillow Top Arms'],
                        ['name' => 'Sloped Arms'],
                        ['name' => 'Flared Arms'],
                        ['name' => 'Tuxedo'],
                    ]
                ],
                'Design' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Chesterfield'],
                        ['name' => 'Curved'],
                        ['name' => 'Standard'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => '5 Feet'],
                        ['name' => '6 Feet'],
                        ['name' => '7 Feet'],
                        ['name' => '8 Feet'],
                        ['name' => '9 Feet'],
                        ['name' => '10 Feet'],
                    ]
                ],
                'Seating Capacity' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Seats 2'],
                        ['name' => 'Seats 3'],
                        ['name' => 'Seats 4'],
                        ['name' => 'Seats 5'],
                        ['name' => 'Seats 6'],
                        ['name' => 'Seats 7'],
                        ['name' => 'Seats 8'],
                    ]
                ],
                'Configuration' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'L-Shaped'],
                        ['name' => 'U-Shaped'],
                        ['name' => 'Curved'],
                    ]
                ],
                'Orientation' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Reversible'],
                        ['name' => 'Left-Facing'],
                        ['name' => 'Right-Facing'],
                    ]
                ],

            ],
            'Love Seats' => [
                'Style' => true,  // Use global attribute
                'Color' => true,
                'Assembly' => true,
                'Frame Material' => true,
                'Back Height' => true,
                'Upholstery Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Cotton'],
                        ['name' => 'Canvas'],
                        ['name' => 'Chenile'],
                        ['name' => 'Suede'],
                        ['name' => 'Vinyl'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Silk'],
                        ['name' => 'Cowhide'],
                        ['name' => 'Synthetic'],
                        ['name' => 'Acrylic'],
                        ['name' => 'Faux Fur'],
                        ['name' => 'Felt'],
                        ['name' => 'Jute & Sisal'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Modular'],
                        ['name' => 'Reclining'],
                        ['name' => 'Storage'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Skirted'],
                        ['name' => 'Wheels'],
                    ]
                ],
                'Cushion Fill' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Feather & Down'],
                        ['name' => 'Polyester'],
                        ['name' => 'Memory Foam'],
                        ['name' => 'Down Alternative'],
                    ]
                ],
                'Arm Style' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Armless'],
                        ['name' => 'One Arm'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Round Arms'],
                        ['name' => 'Pillow Top Arms'],
                        ['name' => 'Sloped Arms'],
                        ['name' => 'Flared Arms'],
                        ['name' => 'Tuxedo'],
                    ]
                ],
            ],
            'Sleeper Sofas/Sofa Beds' => [
                'Style' => true,  // Use global attribute
                'Color' => true,
                'Assembly' => true,
                'Mattress Type' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Innerspring'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Mattress Size' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Queen'],
                        ['name' => 'Full Double'],
                        ['name' => 'Twin'],
                        ['name' => 'King'],
                    ]
                ],
                'Upholstery Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Microsuede & Microfiber'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Reclining'],
                        ['name' => 'Storage'],
                    ]
                ],
                'Cushion Fill' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Arm Style' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Armless'],
                        ['name' => 'Square Arms'],
                        ['name' => 'Round Arms'],
                    ]
                ],
            ],
            'Furniture Sets' => [
                'Style' => true,  // Use global attribute
                'Color' => true,
                'Assembly' => true,
                'Number in Set' => true,
                'Upholstery Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Velvet'],
                        ['name' => 'Leather'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Linen'],
                        ['name' => 'Microsuede & Microfiber'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Modular'],
                        ['name' => 'Reclining'],
                        ['name' => 'Removable Cushions'],
                        ['name' => 'Storage'],
                        ['name' => 'Small-Scale'],
                        ['name' => 'Nailhead Trim'],
                        ['name' => 'Slipcovered'],
                        ['name' => 'Deep Seating'],
                        ['name' => 'Extra Long'],
                        ['name' => 'Skirted'],
                        ['name' => 'Ottoman Included'],
                        ['name' => 'Pillows Included'],
                        ['name' => 'Low Profile'],
                        ['name' => 'Stain Resistant'],
                        ['name' => 'Coils/Springs'],
                        ['name' => 'Distressed Leather'],
                        ['name' => 'Wheels'],
                        ['name' => '8-Way Hand Tied'],
                    ]
                ],
                'Sofa Design' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Stationary Sofa'],
                        ['name' => 'Reclining Sofa'],
                        ['name' => 'Sleeper Sofa'],
                    ]
                ],
            ],
            'Futons' => [
                'Style' => true,
                'Color' => true,
                'Assembly' => true,
                'Frame Material' => true,
                'Upholstery Material' => true,
                'Size' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Full & Double'],
                        ['name' => 'Queen'],
                        ['name' => 'Toddler'],
                    ]
                ],
                'Frame Finish' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Natural Finish'],
                        ['name' => 'Unfinished'],
                        ['name' => 'Chrome'],
                        ['name' => 'Bronze'],
                        ['name' => 'Silver'],
                        ['name' => 'Mahogany'],
                        ['name' => 'Pine'],
                        ['name' => 'White'],
                    ]
                ],
                'Mattress Type' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Gel Foam'],
                        ['name' => 'Pocket Coil'],
                        ['name' => 'Innerspring'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Tufted'],
                        ['name' => 'Reclining'],
                        ['name' => 'Storage'],
                        ['name' => 'Pillows Included'],

                    ]
                ],
            ],
            'Futon Covers' => [
                'Style' => true,
                'Color' => true,
                'Assembly' => true,
                'Pattern' => true,
                'Size' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Full & Double'],
                        ['name' => 'Queen'],
                        ['name' => 'Toddler'],
                    ]
                ],
                'Material' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Polyester'],
                        ['name' => 'Microsuede & Microfiber'],
                        ['name' => 'Cotton'],
                    ]
                ],
                'Fastener' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Zipper'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Skirted'],
                    ]
                ],
            ],
            'Futon Frames' => [
                'Style' => true,
                'Size' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Full & Double'],
                        ['name' => 'Queen'],
                    ]
                ],
                'Frame Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Metal'],
                        ['name' => 'Wood'],
                    ]
                ],
                'Finish' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Brown'],
                        ['name' => 'Espresso'],
                        ['name' => 'White'],
                        ['name' => 'Black'],
                    ]
                ],
                'Features' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Storage'],
                    ]
                ],
            ],
            'Futon Mattresses' => [
                'Style' => true,
                'Assembly' => true,
                'Color' => true,
                'Size' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Full & Double'],
                        ['name' => 'Queen'],
                        ['name' => 'Twin'],
                    ]
                ],
                'Mattress Type' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Foam'],
                        ['name' => 'Memory Foam'],
                    ]
                ],
                'Mattress Support' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Firm'],
                        ['name' => 'Medium'],
                        ['name' => 'Soft'],
                    ]
                ],
            ],
            'Coffee Tables' => [
                'Style' => true,
                'Assembly' => true,
                'Color' => true,
                'Wood Tone' => [
                    'type' => 'select',
                    'is_variant_generator' => true,
                    'values' => [
                        ['name' => 'Dark Wood'],
                        ['name' => 'Light Wood'],
                    ]
                ],
                'Shape' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Novelty'],
                        ['name' => 'Freeform'],
                        ['name' => 'Octagon'],
                        ['name' => 'Hexagon'],
                        ['name' => 'Triangle'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => '30 inches'],
                        ['name' => '36 inches'],
                        ['name' => '40 inches'],
                        ['name' => '42 inches'],
                        ['name' => '48 inches'],
                        ['name' => '60 inches'],
                    ]
                ],
                'Top Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Laminate'],
                        ['name' => 'Concrete'],
                        ['name' => 'Stone'],
                        ['name' => 'Fabric'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Leather'],
                        ['name' => 'Smoked Glass'],
                        ['name' => 'Faux Leather'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Base Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Bamboo'],
                        ['name' => 'Driftwood'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                    ]
                ],
                'Base Design' => [
                    'type' => 'select',
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
                    'type' => 'select',
                    'values' => [
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
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Traytop'],
                        ['name' => 'Shelf'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Turned Legs'],
                        ['name' => 'Oversized'],
                        ['name' => 'Drawers'],
                        ['name' => 'Geometric'],
                        ['name' => 'Lift Top'],
                        ['name' => 'Reclaimed Wood'],
                        ['name' => 'Lacquered Finish'],
                        ['name' => 'Casters'],
                        ['name' => 'Carved'],
                        ['name' => 'Bunching'],
                        ['name' => 'Live Edge'],
                        ['name' => 'Whitewashed'],
                        ['name' => 'Nesting'],
                        ['name' => 'Narrow'],
                        ['name' => 'Magazine Holder'],
                    ]
                ],
            ],
            'Console Tables' => [
                'Style' => true,
                'Assembly' => true,
                'Color' => true,
                'Shape' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Semicircle'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => '36 inches'],
                        ['name' => '50 inches'],
                        ['name' => '60 inches'],
                        ['name' => '70 inches'],
                        ['name' => '72 inches'],
                        ['name' => '80 inches'],
                    ]
                ],
                'Tabletop Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Concrete'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Leather'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Table Base Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                    ]
                ],
                'Base Design' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Pedestal'],
                        ['name' => 'Waterfall'],
                        ['name' => 'Trestle'],
                    ]
                ],
                'Finish' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Natural Finish'],
                        ['name' => 'Oak'],
                        ['name' => 'Transparent'],
                        ['name' => 'Unfinished'],
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
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Traytop'],
                        ['name' => 'Drawers'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Extra Long'],
                        ['name' => 'Casters'],
                        ['name' => 'Hand Painted'],
                        ['name' => 'Flip Top'],
                    ]
                ],
            ],
            'Side & End Tables' => [
                'Style' => true,
                'Assembly' => true,
                'Color' => true,
                'Shape' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Novelty'],
                        ['name' => 'Freeform'],
                        ['name' => 'Octagon'],
                        ['name' => 'Hexagon'],
                        ['name' => 'Triangle'],
                        ['name' => 'Wedge'],
                        ['name' => 'Semicircle'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => 'Tall'],
                        ['name' => '20 inches'],
                        ['name' => '24 inches'],
                        ['name' => '28 inches'],
                        ['name' => '30 inches'],
                        ['name' => '36 inches'],
                    ]
                ],
                'Tabletop Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Stone'],
                        ['name' => 'Mozaic'],
                        ['name' => 'Leather'],
                        ['name' => 'Granite'],
                    ]
                ],
                'Base Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Bamboo'],
                        ['name' => 'Driftwood'],
                        ['name' => 'Stone'],
                        ['name' => 'Wicker & Rattan'],
                        ['name' => 'Plastic & Acrylic'],
                        ['name' => 'Wrought Iron'],
                    ]
                ],
                'Base Design' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Drum'],
                        ['name' => 'Pedestal'],
                        ['name' => 'Solid Base'],
                        ['name' => 'Tripod'],
                        ['name' => 'Waterfall'],
                        ['name' => 'C-Shaped'],
                        ['name' => 'Sled'],
                    ]
                ],
                'Finish' => [
                    'type' => 'select',
                    'values' => [
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
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Adjustable Height'],
                        ['name' => 'Traytop'],
                        ['name' => 'Shelf'],
                        ['name' => 'Folding'],
                        ['name' => 'Distressed Finish'],
                        ['name' => 'Storage'],
                        ['name' => 'Oversized'],
                        ['name' => 'Drawers'],
                        ['name' => 'Geometric'],
                        ['name' => 'Lift Top'],
                        ['name' => 'Reclaimed Wood'],
                        ['name' => 'Drop Leaf'],
                        ['name' => 'Carved'],
                        ['name' => 'Woven'],
                        ['name' => 'Nesting'],
                        ['name' => 'Charging Station'],
                        ['name' => 'Magazine Holder'],
                    ]
                ],
            ],
            'Table Sets' => [
                'Style' => true,
                'Assembly' => true,
                'Color' => true,
                'Shape' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Round'],
                        ['name' => 'Rectangle'],
                        ['name' => 'Square'],
                        ['name' => 'Oval'],
                        ['name' => 'Freeform'],
                    ]
                ],
                'Popular Sizes' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Mini'],
                        ['name' => 'Tall'],
                        ['name' => '20 inches'],
                        ['name' => '24 inches'],
                        ['name' => '28 inches'],
                        ['name' => '30 inches'],
                        ['name' => '36 inches'],
                    ]
                ],
                'Tabletop Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Marble'],
                        ['name' => 'Stone'],
                    ]
                ],
                'Base Material' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Wood'],
                        ['name' => 'Glass'],
                        ['name' => 'Metal'],
                        ['name' => 'Wrought Iron'],
                    ]
                ],
                'Finish' => [
                    'type' => 'select',
                    'values' => [
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
                    'type' => 'select',
                    'values' => [
                        ['name' => 'Lift Top'],
                        ['name' => 'Nesting'],
                        ['name' => 'Table Sets'],
                    ]
                ],
                'Number In Set' => [
                    'type' => 'select',
                    'values' => [
                        ['name' => '2 Piece Set'],
                        ['name' => '3 Piece Set'],
                        ['name' => '4 Piece Set'],
                    ]
                ],
            ],
        ];

        // First, create global attributes and values
        $this->createGlobalAttributes($globalAttributes);

        // Then seed subcategory-specific attributes
        $this->seedSubcategoryAttributes($subcategoryAttributes, $globalAttributes);
    }

    private function createGlobalAttributes(array $globalAttributes)
    {
        foreach ($globalAttributes as $attributeName => $attributeData) {
            $attribute = Attribute::firstOrCreate([
                'name' => $attributeName,
                'type' => $attributeData['type'] ?? 'select',
                'is_variant_generator' => $attributeData['is_variant_generator'] ?? false
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
                        $subcategory->attributes()->syncWithoutDetaching($attribute);
                    }
                    continue;
                }

                // Handle overridden or new attributes
                $attribute = Attribute::firstOrCreate([
                    'name' => $attributeName,
                    'type' => $attributeConfig['type'] ?? 'select',
                    'is_variant_generator' => $attributeConfig['is_variant_generator'] ?? false
                ]);

                // Attach attribute to subcategory
                $subcategory->attributes()->syncWithoutDetaching($attribute);

                // Create or use values
                if (isset($attributeConfig['values'])) {
                    $values = collect($attributeConfig['values'])->map(function ($valueData) use ($attribute) {
                        return AttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'name' => $valueData['name']
                        ], [
                            'representation' => $valueData['representation'] ?? null
                        ]);
                    });

                    // Attach values to subcategory
                    $subcategory->attributeValues()->syncWithoutDetaching(
                        $values->pluck('id')->mapWithKeys(fn($id) => [$id => ['attribute_id' => $attribute->id]])
                    );
                }
            }
        }
    }
}
