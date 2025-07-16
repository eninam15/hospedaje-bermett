<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin principal
        $admin = User::create([
            'name' => 'Administrador Principal',
            'email' => 'admin@bermett.com',
            'password' => Hash::make('admin123'),
            'phone' => '+591 70000000',
            'document_type' => 'ci',
            'document_number' => '12345678',
            'address' => 'La Paz, Bolivia',
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $admin->assignRole('admin');

        // Empleado
        $employee = User::create([
            'name' => 'María Quispe',
            'email' => 'empleado@bermett.com',
            'password' => Hash::make('empleado123'),
            'phone' => '+591 70000001',
            'document_type' => 'ci',
            'document_number' => '87654321',
            'address' => 'El Alto, Bolivia',
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $employee->assignRole('employee');

        // Cliente de prueba
        $customer = User::create([
            'name' => 'Juan Pérez',
            'email' => 'cliente@bermett.com',
            'password' => Hash::make('cliente123'),
            'phone' => '+591 70000002',
            'document_type' => 'ci',
            'document_number' => '11223344',
            'address' => 'Cochabamba, Bolivia',
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $customer->assignRole('customer');
    }
}