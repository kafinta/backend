<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Color;
use App\Models\SubCategory;
class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Color::truncate();
        $subcategories = [
            Subcategory::find(1),
            Subcategory::find(2),
            Subcategory::find(3),
            Subcategory::find(4),
            Subcategory::find(5),
            Subcategory::find(6),
            Subcategory::find(7),
            Subcategory::find(8),
            Subcategory::find(9),
            Subcategory::find(20),
            Subcategory::find(21),

        ];

        $colors = [
            [
                'name' => 'Black',
                'hex_code' => '000000',
            ],
            [
                'name' => 'White',
                'hex_code' => 'FFFFFF',
            ],
            [
                'name' => 'Red',
                'hex_code' => 'FF0000',
            ],
            [
                'name' => 'Green',
                'hex_code' => '00FF00',
            ],
            [
                'name' => 'Blue',
                'hex_code' => '0000FF',
            ],
            [
                'name' => 'Gray',
                'hex_code' => '808080',
            ],
            [
                'name' => 'Yellow',
                'hex_code' => 'FFFF00',
            ],
            [
                'name' => 'Orange',
                'hex_code' => 'FF7F00',
            ],
            [
                'name' => 'Purple',
                'hex_code' => '800080',
            ],
            [
                'name' => 'Pink',
                'hex_code' => 'FF69B4',
            ],
            [
                'name' => 'Beige',
                'hex_code' => 'E3DAC9',
            ],
            [
                'name' => 'Beige',
                'hex_code' => 'FF69B4',
            ],
            [
                'name' => 'Burgundy',
                'hex_code' => 'FF69B4',
            ],
            [
                'name' => 'Black & White',
            ],
            [
                'name' => 'Multi',
            ],
            [
                'name' => 'Colorful',
            ]
        ];

        foreach ($subcategories as $subcategory) {
            foreach ($colors as $color) {
                $subcategory->colors()->create($color);
            }
        }
    }
}
