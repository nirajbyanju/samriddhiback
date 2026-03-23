<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ✅ All feature permissions
        $permissions = [
            'view_dashboard',
            'view_employees','create_employees','edit_employees','delete_employees',
            'view_menus','create_menus','edit_menus','delete_menus',
            'view_reports','export_reports',
            'view_settings','edit_settings',
            'view_products','create_products','edit_products','delete_products',
            'view_orders','create_orders','edit_orders','approve_orders',
            'view_inventory','edit_inventory','upload_inventory',
            'manage_all'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ✅ Roles
        $roles = ['Super Admin','Admin','Manager','Employee'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Assign all permissions to Super Admin
        Role::findByName('Super Admin')->givePermissionTo(Permission::all());

        // Assign Admin permissions
        Role::findByName('Admin')->givePermissionTo([
            'view_dashboard',
            'view_employees','create_employees','edit_employees','delete_employees',
            'view_menus','create_menus','edit_menus','delete_menus',
            'view_reports','export_reports',
            'view_settings','edit_settings',
            'view_products','create_products','edit_products','delete_products',
            'view_orders','create_orders','edit_orders','approve_orders',
            'view_inventory','edit_inventory','upload_inventory',
        ]);

        // Assign Manager permissions
        Role::findByName('Manager')->givePermissionTo([
            'view_dashboard',
            'view_employees',
            'view_reports','export_reports',
            'view_products','create_products','edit_products',
            'view_orders','create_orders','edit_orders',
            'view_inventory','edit_inventory',
        ]);

        // Assign Employee permissions
        Role::findByName('Employee')->givePermissionTo([
            'view_dashboard',
            'view_products',
            'view_orders','create_orders',
            'view_inventory',
        ]);
    }
}