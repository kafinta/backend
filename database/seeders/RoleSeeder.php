<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        Role::create(['name' => 'Seller', 'slug' => 'seller']);
        Role::create(['name' => 'Customer', 'slug' => 'customer']);
    }
}
