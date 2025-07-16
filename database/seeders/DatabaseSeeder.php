<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolePermissionSeeder::class,
            BranchSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            ServiceSeeder::class,
            UserSeeder::class,
        ]);
    }
}