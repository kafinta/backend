<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Role;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create seller accounts
        $sellers = $this->createSellers();

        // Get subcategories for seeding
        $sofasSubcategory = Subcategory::where('name', 'Sofas and Couches')->first();
        $bedsSubcategory = Subcategory::where('name', 'Beds')->first();
        $lightingSubcategory = Subcategory::where('name', 'Pendant Lights')->first()
            ?? Subcategory::where('name', 'Ceiling Lights')->first();

        // Seed products in different subcategories
        $this->seedSofaProducts($sellers, $sofasSubcategory);
        $this->seedBedProducts($sellers, $bedsSubcategory);
        $this->seedLightingProducts($sellers, $lightingSubcategory);
    }

    /**
     * Create seller accounts for testing
     */
    private function createSellers()
    {
        $sellerRole = Role::where('slug', 'seller')->first();
        $sellers = [];

        $sellerData = [
            [
                'username' => 'furniture_store',
                'email' => 'furniture@test.com',
                'business_name' => 'Premium Furniture Store',
                'business_description' => 'High-quality furniture for modern homes',
                'business_address' => '123 Furniture Lane, Design City',
                'phone_number' => '+1-555-0101',
            ],
            [
                'username' => 'home_decor_pro',
                'email' => 'homedecor@test.com',
                'business_name' => 'Home Decor Pro',
                'business_description' => 'Elegant home furnishings and lighting solutions',
                'business_address' => '456 Decor Avenue, Style Town',
                'phone_number' => '+1-555-0102',
            ],
            [
                'username' => 'modern_living',
                'email' => 'modernliving@test.com',
                'business_name' => 'Modern Living Co.',
                'business_description' => 'Contemporary furniture and home accessories',
                'business_address' => '789 Modern Street, Trend City',
                'phone_number' => '+1-555-0103',
            ],
        ];

        foreach ($sellerData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]
            );

            // Attach seller role
            if (!$user->hasRole('seller')) {
                $user->roles()->attach($sellerRole);
            }

            // Create or update seller profile
            Seller::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $data['business_name'],
                    'business_description' => $data['business_description'],
                    'business_address' => $data['business_address'],
                    'phone_number' => $data['phone_number'],
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'profile_completed_at' => now(),
                    'kyc_verified_at' => now(),
                    'agreement_accepted' => true,
                    'agreement_accepted_at' => now(),
                    'onboarding_completed_at' => now(),
                    'onboarding_progress' => 100,
                ]
            );

            $sellers[] = $user;
        }

        return $sellers;
    }

    /**
     * Seed sofa products
     */
    private function seedSofaProducts($sellers, $subcategory)
    {
        if (!$subcategory) return;

        $sofaProducts = [
            [
                'name' => 'Modern Gray Sectional Sofa',
                'description' => 'Spacious L-shaped sectional sofa with comfortable cushions. Perfect for large living rooms. Features durable fabric and wooden frame.',
                'price' => 1299.99,
                'stock_quantity' => 15,
            ],
            [
                'name' => 'Classic Leather Sofa',
                'description' => 'Premium leather sofa with elegant design. Comfortable seating for 3-4 people. Easy to clean and maintain.',
                'price' => 1899.99,
                'stock_quantity' => 8,
            ],
            [
                'name' => 'Compact Apartment Sofa',
                'description' => 'Perfect for small spaces. Stylish and comfortable 2-seater sofa. Available in multiple colors.',
                'price' => 599.99,
                'stock_quantity' => 25,
            ],
            [
                'name' => 'Luxury Velvet Sofa',
                'description' => 'Luxurious velvet upholstery with modern design. Adds elegance to any living room. Includes decorative pillows.',
                'price' => 2199.99,
                'stock_quantity' => 5,
            ],
        ];

        foreach ($sofaProducts as $index => $product) {
            Product::create([
                'name' => $product['name'],
                'slug' => Str::slug($product['name']) . '-' . uniqid(),
                'description' => $product['description'],
                'price' => $product['price'],
                'user_id' => $sellers[$index % count($sellers)]->id,
                'subcategory_id' => $subcategory->id,
                'status' => 'active',
                'stock_quantity' => $product['stock_quantity'],
                'manage_stock' => true,
            ]);
        }
    }

    /**
     * Seed bed products
     */
    private function seedBedProducts($sellers, $subcategory)
    {
        if (!$subcategory) return;

        $bedProducts = [
            [
                'name' => 'Queen Size Platform Bed',
                'description' => 'Sturdy platform bed with storage drawers. Solid wood construction. Fits standard queen mattress.',
                'price' => 799.99,
                'stock_quantity' => 12,
            ],
            [
                'name' => 'King Size Upholstered Bed',
                'description' => 'Luxurious upholstered bed frame with headboard. Perfect for master bedrooms. Includes slats.',
                'price' => 1299.99,
                'stock_quantity' => 7,
            ],
            [
                'name' => 'Twin Bed with Trundle',
                'description' => 'Space-saving twin bed with pull-out trundle. Ideal for kids rooms or guest bedrooms.',
                'price' => 449.99,
                'stock_quantity' => 20,
            ],
            [
                'name' => 'Adjustable Electric Bed',
                'description' => 'Modern adjustable bed with remote control. Customizable firmness and position. Queen size.',
                'price' => 1599.99,
                'stock_quantity' => 4,
            ],
        ];

        foreach ($bedProducts as $index => $product) {
            Product::create([
                'name' => $product['name'],
                'slug' => Str::slug($product['name']) . '-' . uniqid(),
                'description' => $product['description'],
                'price' => $product['price'],
                'user_id' => $sellers[$index % count($sellers)]->id,
                'subcategory_id' => $subcategory->id,
                'status' => 'active',
                'stock_quantity' => $product['stock_quantity'],
                'manage_stock' => true,
            ]);
        }
    }

    /**
     * Seed lighting products
     */
    private function seedLightingProducts($sellers, $subcategory)
    {
        if (!$subcategory) return;

        $lightingProducts = [
            [
                'name' => 'Modern LED Pendant Light',
                'description' => 'Contemporary pendant light with LED technology. Energy-efficient and long-lasting. Dimmable.',
                'price' => 89.99,
                'stock_quantity' => 30,
            ],
            [
                'name' => 'Crystal Chandelier',
                'description' => 'Elegant crystal chandelier for dining rooms. Adds sophistication to any space. Includes 6 bulbs.',
                'price' => 349.99,
                'stock_quantity' => 6,
            ],
            [
                'name' => 'Minimalist Floor Lamp',
                'description' => 'Sleek floor lamp with adjustable brightness. Perfect for reading or ambient lighting.',
                'price' => 129.99,
                'stock_quantity' => 18,
            ],
            [
                'name' => 'Smart RGB Ceiling Light',
                'description' => 'WiFi-enabled ceiling light with color changing options. Control via smartphone app.',
                'price' => 199.99,
                'stock_quantity' => 10,
            ],
        ];

        foreach ($lightingProducts as $index => $product) {
            Product::create([
                'name' => $product['name'],
                'slug' => Str::slug($product['name']) . '-' . uniqid(),
                'description' => $product['description'],
                'price' => $product['price'],
                'user_id' => $sellers[$index % count($sellers)]->id,
                'subcategory_id' => $subcategory->id,
                'status' => 'active',
                'stock_quantity' => $product['stock_quantity'],
                'manage_stock' => true,
            ]);
        }
    }
}
