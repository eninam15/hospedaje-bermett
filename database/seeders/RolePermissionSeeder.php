<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos
        $permissions = [
            // Usuarios
            'view users', 'create users', 'edit users', 'delete users',
            // Sucursales
            'view branches', 'create branches', 'edit branches', 'delete branches',
            // Habitaciones
            'view rooms', 'create rooms', 'edit rooms', 'delete rooms',
            // Servicios
            'view services', 'create services', 'edit services', 'delete services',
            // Reservas
            'view all reservations', 'edit reservations', 'cancel reservations',
            'view own reservations', 'create reservations',
            // Pagos
            'view payments', 'verify payments', 'reject payments',
            // Registros
            'view registrations', 'create registrations', 'edit registrations',
            'view own registrations',
            // Consumos
            'view all consumptions', 'create consumptions', 'edit consumptions',
            'view own consumptions',
            // Reportes
            'view reports', 'export reports',
            // Perfil
            'update own profile'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::create(['name' => 'admin']);
        $employeeRole = Role::create(['name' => 'employee']);
        $customerRole = Role::create(['name' => 'customer']);

        // Asignar permisos a admin
        $adminRole->givePermissionTo(Permission::all());

        // Asignar permisos a employee
        $employeeRole->givePermissionTo([
            'view rooms', 'edit rooms',
            'view all reservations', 'edit reservations',
            'view registrations', 'create registrations', 'edit registrations',
            'view all consumptions', 'create consumptions', 'edit consumptions',
            'view payments'
        ]);

        // Asignar permisos a customer
        $customerRole->givePermissionTo([
            'view own reservations', 'create reservations',
            'view own registrations',
            'view own consumptions',
            'update own profile'
        ]);
    }
}