<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        // Servicios Villa Caluyo (Sucursal 1)
        $services_villa_caluyo = [
            ['Estacionamiento', 'Estacionamiento seguro', 'parking', 15],
            ['Desayuno', 'Desayuno continental', 'food', 25],
            ['Almuerzo', 'Almuerzo completo', 'food', 35],
            ['Cena', 'Cena completa', 'food', 45],
            ['Lavandería', 'Servicio de lavandería', 'laundry', 20],
            ['Snacks', 'Snacks y bebidas', 'other', 8],
        ];

        foreach ($services_villa_caluyo as $service) {
            Service::create([
                'branch_id' => 1,
                'name' => $service[0],
                'description' => $service[1],
                'category' => $service[2],
                'price' => $service[3],
                'is_available' => true
            ]);
        }

        // Servicios Cruce Villa Adela (Sucursal 2)
        $services_villa_adela = [
            ['Estacionamiento VIP', 'Estacionamiento cubierto', 'parking', 18],
            ['Desayuno Premium', 'Desayuno buffet', 'food', 35],
            ['Almuerzo Familiar', 'Almuerzo para compartir', 'food', 50],
            ['Cena Romántica', 'Cena especial para dos', 'food', 80],
            ['Lavandería Premium', 'Servicio de lavandería express', 'laundry', 30],
            ['Minibar', 'Bebidas y snacks premium', 'other', 15],
        ];

        foreach ($services_villa_adela as $service) {
            Service::create([
                'branch_id' => 2,
                'name' => $service[0],
                'description' => $service[1],
                'category' => $service[2],
                'price' => $service[3],
                'is_available' => true
            ]);
        }
    }
}