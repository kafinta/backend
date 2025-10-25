<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Copy seeder images to storage before seeding
        $this->command->info('Copying seeder images to storage...');
        Artisan::call('seed:copy-images');
        $this->command->info(Artisan::output());

        $this->call([
            RoleSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
            AttributeValueSeeder::class,
            ProductSeeder::class
        ]);
    }
}
