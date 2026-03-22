<?php
// database/seeders/PermissionMatrixSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\PermissionMatrix;
use App\Models\User;
use App\Models\Menu;
use Illuminate\Support\Str;

class PermissionMatrixSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view menus',
            'create menus',
            'edit menus',
            'delete menus',
            'view reports',
            'export reports',
            'view settings',
            'edit settings',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view orders',
            'create orders',
            'edit orders',
            'approve orders',
            'view inventory',
            'edit inventory',
            'upload inventory',
            'manage all'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $admin = Role::create(['name' => 'Admin']);
        $manager = Role::create(['name' => 'Manager']);
        $employee = Role::create(['name' => 'Employee']);
        $user = Role::create(['name' => 'User']);
        
        // Assign all permissions to Super Admin
        $superAdmin->givePermissionTo(Permission::all());

        // Define features and their permissions for each role
        $rolePermissions = [
            'Admin' => [
                'dashboard' => ['view' => true],
                'employees' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'menus' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'reports' => ['view' => true, 'export' => true],
                'settings' => ['view' => true, 'edit' => true],
                'products' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'orders' => ['view' => true, 'create' => true, 'edit' => true, 'approve' => true],
                'inventory' => ['view' => true, 'edit' => true, 'upload' => true],
            ],
            'Manager' => [
                'dashboard' => ['view' => true],
                'employees' => ['view' => true],
                'reports' => ['view' => true, 'export' => true],
                'products' => ['view' => true, 'create' => true, 'edit' => true],
                'orders' => ['view' => true, 'create' => true, 'edit' => true],
                'inventory' => ['view' => true, 'edit' => true],
            ],
            'Employee' => [
                'dashboard' => ['view' => true],
                'products' => ['view' => true],
                'orders' => ['view' => true, 'create' => true],
                'inventory' => ['view' => true],
            ]
        ];

        // Create permission matrix for each role
        foreach ($rolePermissions as $roleName => $features) {
            $role = Role::where('name', $roleName)->first();
            
            foreach ($features as $feature => $perms) {
                $matrix = PermissionMatrix::create([
                    'feature_name' => $feature,
                    'permission_key' => $feature . '_' . $role->id,
                    'can_view' => $perms['view'] ?? false,
                    'can_create' => $perms['create'] ?? false,
                    'can_edit' => $perms['edit'] ?? false,
                    'can_delete' => $perms['delete'] ?? false,
                    'can_approve' => $perms['approve'] ?? false,
                    'can_export' => $perms['export'] ?? false,
                    'can_upload' => $perms['upload'] ?? false,
                    'can_all' => $perms['all'] ?? false,
                    'role_id' => $role->id,
                ]);
                
                // Create Spatie permissions
                $this->createSpatiePermissions($matrix, $role);
            }
        }

        // Create sample menus
        $menus = [
            [
                'name' => 'Dashboard',
                'icon' => 'dashboard',
                'route' => 'dashboard',
                'order' => 1,
                'is_status' => 1,
            ],
            [
                'name' => 'Employees',
                'icon' => 'users',
                'route' => 'employees.index',
                'order' => 2,
                'is_status' => 1,
                'permission_name' => 'view employees',
                'children' => [
                    [
                        'name' => 'All Employees',
                        'route' => 'employees.index',
                        'order' => 1,
                        'permission_name' => 'view employees'
                    ],
                    [
                        'name' => 'Add Employee',
                        'route' => 'employees.create',
                        'order' => 2,
                        'permission_name' => 'create employees'
                    ]
                ]
            ],
            [
                'name' => 'Products',
                'icon' => 'products',
                'route' => 'products.index',
                'order' => 3,
                'is_status' => 1,
                'permission_name' => 'view products',
            ],
            [
                'name' => 'Orders',
                'icon' => 'orders',
                'route' => 'orders.index',
                'order' => 4,
                'is_status' => 1,
                'permission_name' => 'view orders',
            ],
            [
                'name' => 'Reports',
                'icon' => 'reports',
                'route' => 'reports.index',
                'order' => 5,
                'is_status' => 1,
                'permission_name' => 'view reports',
            ],
            [
                'name' => 'Settings',
                'icon' => 'settings',
                'route' => 'settings.index',
                'order' => 6,
                'is_status' => 1,
                'permission_name' => 'view settings',
            ]
        ];

        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);
            
            $menu = Menu::create($menuData);
            
            foreach ($children as $childData) {
                $childData['parent_id'] = $menu->id;
                Menu::create($childData);
            }
        }

        // Create test users
        $this->createTestUsers();
    }

    private function createSpatiePermissions($matrix, $role)
    {
        $feature = Str::slug($matrix->feature_name);
        
        $permissionMappings = [
            'can_view' => "view {$feature}",
            'can_create' => "create {$feature}",
            'can_edit' => "edit {$feature}",
            'can_delete' => "delete {$feature}",
            'can_approve' => "approve {$feature}",
            'can_export' => "export {$feature}",
            'can_upload' => "upload {$feature}",
            'can_all' => "manage {$feature}",
        ];

        foreach ($permissionMappings as $field => $permissionName) {
            if ($matrix->$field) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                $role->givePermissionTo($permission);
            }
        }
    }

    private function createTestUsers()
    {
        $admin = User::create([
            'userCode' => 'sm-2026-1',
            'first_name' => 'Niraj',
            'last_name' => 'Byanju',
            'username' => 'nirajbyanju1234',
            'email' => 'nirajbyanju1234@gmail.com', // <-- Fixed missing quote
            'email_verified_at' => now(),
            'phone' => '+919876543210', // <-- Fixed missing quote
            'password' => bcrypt('password'), // password
            'remember_token' => Str::random(10),
        ]);
        $admin->assignRole('Super Admin');

        $manager = User::create([
            'userCode' => 'sm-2026-2',
            'first_name' => 'Niraj',
            'last_name' => 'Byanju',
            'username' => 'byanju',
            'email' => 'byanju@example.com',
            'email_verified_at' => now(),
            'phone' => '+91987693210', // <-- Fixed missing quote
            'password' => bcrypt('password'), // password
            'remember_token' => Str::random(10),
        ]);
        $manager->assignRole('Manager');

        $employee = User::create([
            'userCode' => 'sm-2026-3',
            'first_name' => 'Niraj',
            'last_name' => 'Byanju',
            'username' => 'byanju1',
            'email' => 'byanju1@example.com',
            'email_verified_at' => now(),
            'phone' => '+91987593210', // <-- Fixed missing quote
            'password' => bcrypt('password'), // password
            'remember_token' => Str::random(10),
        ]);
        $employee->assignRole('Employee');
    }
}