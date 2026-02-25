<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'property.create','property.edit','property.delete','property.view'
        ];

        foreach($permissions as $perm){
            Permission::create(['name'=>$perm]);
        }

        $roles = ['admin','manager','agent','employee','user'];

        foreach($roles as $roleName){
            $role = Role::create(['name'=>$roleName]);
        }

        // Role::findByName('admin')->givePermissionTo($permissions);
        // Role::findByName('manager')->givePermissionTo(['property.create','property.edit','property.view']);
        // Role::findByName('agent')->givePermissionTo(['property.create','property.edit','property.view']);
        // Role::findByName('employee')->givePermissionTo(['property.view']);
    }
}
