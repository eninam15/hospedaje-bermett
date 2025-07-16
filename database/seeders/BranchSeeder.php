<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run()
    {
        Branch::create([
            'name' => 'Sucursal 1 - Villa Caluyo',
            'category' => '3 estrellas',
            'address' => 'Villa Caluyo D #784 Entre Calle 5 y 6',
            'phone' => '+591 71234567',
            'email' => 'villa.caluyo@bermett.com',
            'city' => 'El Alto',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'manager_name' => 'Carlos Mamani',
            'description' => 'Hospedaje cómodo y económico en Villa Caluyo',
            'qr_payment_info' => 'Yape: 999888777 | BCP: 123-456-789',
            'is_active' => true
        ]);

        Branch::create([
            'name' => 'Sucursal 2 - Cruce Villa Adela',
            'category' => '4 estrellas',
            'address' => 'Zona Cruce Villa Adela, Av. Ladislao Cabrera, calle 1 N.º 2094',
            'phone' => '+591 72345678',
            'email' => 'villa.adela@bermett.com',
            'city' => 'El Alto',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'manager_name' => 'Ana Quispe',
            'description' => 'Hospedaje premium con servicios de calidad',
            'qr_payment_info' => 'Yape: 888777666 | BCP: 987-654-321',
            'is_active' => true
        ]);
    }
}