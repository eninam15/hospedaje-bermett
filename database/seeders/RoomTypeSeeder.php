<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    public function run()
    {
        RoomType::create([
            'name' => 'Simple',
            'description' => 'Habitación económica para 1 persona',
            'max_adults' => 1,
            'max_children' => 0,
            'amenities' => ['Cama individual', 'TV', 'WiFi']
        ]);

        RoomType::create([
            'name' => 'Estándar',
            'description' => 'Habitación cómoda para 2 personas',
            'max_adults' => 2,
            'max_children' => 1,
            'amenities' => ['Cama doble', 'Baño privado', 'TV', 'WiFi']
        ]);

        RoomType::create([
            'name' => 'Matrimonial',
            'description' => 'Habitación para pareja',
            'max_adults' => 2,
            'max_children' => 1,
            'amenities' => ['Cama matrimonial', 'Baño privado', 'TV', 'WiFi', 'Espejo grande']
        ]);

        RoomType::create([
            'name' => 'Individual',
            'description' => 'Habitación para 1 persona',
            'max_adults' => 1,
            'max_children' => 0,
            'amenities' => ['Cama individual', 'Baño privado', 'TV', 'WiFi']
        ]);

        RoomType::create([
            'name' => 'Doble',
            'description' => 'Habitación para 2 personas',
            'max_adults' => 2,
            'max_children' => 1,
            'amenities' => ['Cama matrimonial', 'Baño privado', 'TV', 'WiFi', 'Escritorio']
        ]);

        RoomType::create([
            'name' => 'Triple',
            'description' => 'Habitación para 3 personas',
            'max_adults' => 3,
            'max_children' => 1,
            'amenities' => ['Cama matrimonial', 'Cama individual', 'Baño privado', 'TV', 'WiFi', 'Escritorio']
        ]);

        RoomType::create([
            'name' => 'Suite',
            'description' => 'Habitación premium para 4 personas',
            'max_adults' => 4,
            'max_children' => 2,
            'amenities' => ['Cama king size', 'Sofá cama', 'Baño privado', 'TV', 'WiFi', 'Escritorio', 'Minibar', 'Balcón']
        ]);
    }
}