<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //creamos los roles para los usuarios del sistema
        $role1 = Role::create(['name'=>'admin']);
        $role2 = Role::create(['name'=>'cliente']);

        //definiendo algunos permisos
        $permission1 = Permission::create(['name'=>'Gestion de reservaciones']);  
        $permission2 = Permission::create(['name'=>'Generar Reservas']);

    }
}
