<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run()
    {
        // Habitaciones Villa Caluyo (Sucursal 1)
        $rooms_villa_caluyo = [
            ['101', 1, 1, 85], // Individual
            ['102', 1, 2, 125], // Doble
            ['103', 1, 3, 155], // Triple
            ['201', 2, 2, 130], // Doble
            ['202', 2, 4, 220], // Suite
        ];

        foreach ($rooms_villa_caluyo as $room) {
            Room::create([
                'branch_id' => 1,
                'room_type_id' => $room[2],
                'room_number' => $room[0],
                'floor' => $room[1],
                'price_per_night' => $room[3],
                'status' => 'available',
                'description' => 'Habitación cómoda con todas las comodidades',
                'photos' => ['/images/rooms/default.jpg'],
                'amenities' => ['Agua caliente', 'Ropa de cama', 'Toallas'],
                'is_active' => true
            ]);
        }

        // Habitaciones Cruce Villa Adela (Sucursal 2)
        $rooms_villa_adela = [
            ['101', 1, 1, 90], // Individual
            ['102', 1, 2, 130], // Doble
            ['201', 2, 2, 135], // Doble
            ['202', 2, 3, 160], // Triple
            ['203', 2, 4, 250], // Suite
        ];

        foreach ($rooms_villa_adela as $room) {
            Room::create([
                'branch_id' => 2,
                'room_type_id' => $room[2],
                'room_number' => $room[0],
                'floor' => $room[1],
                'price_per_night' => $room[3],
                'status' => 'available',
                'description' => 'Habitación premium con servicios de calidad',
                'photos' => ['/images/rooms/default.jpg'],
                'amenities' => ['Agua caliente', 'Ropa de cama', 'Toallas', 'Amenities'],
                'is_active' => true
            ]);
        }
    }
}