<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\Location;
use App\Models\SubCategory;

class AttributeSeeder extends Seeder
{
  public function run() {
    $assembly = Attribute::create(['name' => 'Assembly']);
    $style = Attribute::create(['name' => 'Style']);
    $shipping = Attribute::create(['name' => 'Shipping']);
    $type = Attribute::create(['name' => 'Type']);
    $dimensions = Attribute::create(['name' => 'Dimensions']);
    $sizes = Attribute::create(['name' => 'Popular Sizes']);
    $design = Attribute::create(['name' => 'Design']);
    $upholstery = Attribute::create(['name' => 'Upholstery']);
    $upholstery_fabric = Attribute::create(['name' => 'Upholstery Fabric']);
    $upholstery_material = Attribute::create(['name' => 'Upholstery Material']);
    $frame_material = Attribute::create(['name' => 'Frame Material']);
    $material = Attribute::create(['name' => 'Material']);
    $fastener = Attribute::create(['name' => 'Fastener']);
    $frame_finish = Attribute::create(['name' => 'Frame Finish']);
    $features = Attribute::create(['name' => 'Features']);
    $cushion_fill = Attribute::create(['name' => 'Cushion Fill']);
    $arm_style = Attribute::create(['name' => 'Arm Style']);
    $back_type = Attribute::create(['name' => 'Back Type']);
    $back_cushion_type = Attribute::create(['name' => 'Back Cushion Type']);
    $pattern = Attribute::create(['name' => 'Pattern']);
    $back_height = Attribute::create(['name' => 'Back Height']);
    $seating_capacity = Attribute::create(['name' => 'Seating Capacity']);
    $configuration = Attribute::create(['name' => 'Configuration']);
    $orientation = Attribute::create(['name' => 'Orientation']);
    $no_in_set = Attribute::create(['name' => 'Number in Set']);
    $no_of_shelves = Attribute::create(['name' => 'Number of Shelves']);
    $mattress_type = Attribute::create(['name' => 'Mattress Type']);
    $mattress_size = Attribute::create(['name' => 'Mattress Size']);
    $mattress_support = Attribute::create(['name' => 'Mattress Support']);
    $sofa_design = Attribute::create(['name' => 'Sofa Design']);
    $print = Attribute::create(['name' => 'Print']);
    $futon_size = Attribute::create(['name' => 'Futon Size']);
    $shape = Attribute::create(['name' => 'Shape']);
    $wood_tone = Attribute::create(['name' => 'Wood Tone']);
    $tabletop_material = Attribute::create(['name' => 'Table Top Material']);
    $table_base_material = Attribute::create(['name' => 'Table Base Material']);
    $top_material = Attribute::create(['name' => 'Top Material']);
    $base_material = Attribute::create(['name' => 'Base Material']);
    $base_design = Attribute::create(['name' => 'Base Design']);
    $back_panel = Attribute::create(['name' => 'Back Panel']);
    $finish = Attribute::create(['name' => 'Finish']);
    $chair_design = Attribute::create(['name' => 'Chair Design']);
    $quantity = Attribute::create(['name' => 'Quantity']);
    $weight_limit = Attribute::create(['name' => 'Weight Limit']);
    $chair_type = Attribute::create(['name' => 'Chair Type']);
    $massage_technique = Attribute::create(['name' => 'Massage Technique']);


    $sofasandsectionals = Subcategory::where('name', 'Sofas & Sectionals')->first();
    $futonsandaccessories = Subcategory::where('name', 'Futons & Accessories')->first();

    // generalAttributes
    $generalAttributes = [
      $style->id => ['value' => 'Contemporary, Modern, Traditional, Mid-Century Modern, Farmhouse, Transitional, Industrial, Coastal, Victorian, Scandinavian, Rustic, Eclectic, Southwestern, Asian, Tropical, Craftsman, Mediterranean'],

      $cushion_fill->id => ['value' => 'Foam, Feather & Down, Polyester, Memory Foam, Down Alternative']
    ];


    $livingRoom = [
      $upholstery_material->id => ['value' => 'Polyester,Velvet,Leather,Faux Leather,Linen,Microsuede & Microfiber,Chenille,Canvas,Cotton,Vinyl,Suede,Felt,Silk, Cowhide,Synthetic,Acrylic,Faux Fur,Wool,Jute & Sisal, Satin'],
      $arm_style->id => ['value' => 'Square Arms, Armless, Round Arms, Pillow Top Arms, Sloped Arms, Flared Arms, Tuxedo, One Arm'],
      $back_type->id => ['value' => 'Tight Back, Cushion Back, Channel Back, Pillow Back, Camel Back'],
      $frame_material->id => ['value' => 'Manufactured Wood, Metal, Plastic & Acrylic, Wicker & Rattan, Wood'],
      $pattern->id => ['value' => 'Solid Color,Striped, Novelty, Nature & Floral, Geometric, Animal Print, Chevron, Moroccan'],
      $design->id => ['value' => 'Chesterfield, Curved, Standard'],
      $assembly->id => ['value' => 'Fully Assembled'],
      $back_type->id => ['value' => 'Low Back, High Back'],
      $configuration->id => ['value' => 'L Shape, U Shape, Curved'],
      $orientation->id => ['value' => 'Reversible, Left-Facing, Right-Facing'],
      $seating_capacity->id => ['value' => 'Seats 2, Seats 3, Seats 4, Seats 5, Seats 6, Seats 7, Seats 8'],
      $no_of_shelves->id => ['value' => '2 Piece Set, 3 Piece Set, 4 Piece Set, 5 Piece Set, 6 Piece Set, 7 Piece Set'],
      $mattress_type->id => ['value' => 'Foam, Memory Foam, Inner Spring, Pocket Coil, Gel Foam'],
      $mattress_size->id => ['value' => 'Full & Double, Queen, Twin, King'],
      $sofa_design->id => ['value' => 'Stationary Sofa, Reclining Sofa, Sleeper Sofa'],
      $finish->id => ['value' => 'Brown, Black, White, Espresso'],
    ];


        // Prepare the data for attaching, reusing attribute sets
    $subcategoryAttributes = [
      $sofasandsectionals->id => $livingRoom + $materialSet,
      $futonsandaccessories->id => $livingRoom + [
        $features->id => ['value' => 'Tufted, Storage, Reclining, Pillows Included']
      ],
      
    ];

    // Attach attributes to subcategories
    foreach ($subcategoryAttributes as $subcategoryId => $attributes) {
      $subcategory = Subcategory::find($subcategoryId);
      $subcategory->attributes()->attach($attributes);
    }
  }
}