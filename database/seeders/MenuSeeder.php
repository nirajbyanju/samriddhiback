<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'route' => 'dashboard',
                'order' => 1,
                'status' => 1,
                'permission_name' => 'dashboard'
            ],
            [
                'name'=>'Employees',
                'icon'=>'fas fa-users',
                'route'=>'employees.index',
                'order'=>2,
                'status'=>1,
                'permission_name'=>'employees',
                'children'=>[
                    ['name'=>'All Employees','route'=>'employees.index','order'=>1,'permission_name'=>'employees'],
                    ['name'=>'Add Employee','route'=>'employees.create','order'=>2,'permission_name'=>'employees'],
                ]
            ],
            [
                'name'=>'Products',
                'icon'=>'fas fa-boxes',
                'route'=>'products.index',
                'order'=>3,
                'status'=>1,
                'permission_name'=>'products'
            ],
            [
                'name'=>'Orders',
                'icon'=>'fas fa-shopping-cart',
                'route'=>'orders.index',
                'order'=>4,
                'status'=>1,
                'permission_name'=>'orders'
            ],
            [
                'name'=>'Reports',
                'icon'=>'fas fa-chart-bar',
                'route'=>'reports.index',
                'order'=>5,
                'status'=>1,
                'permission_name'=>'reports'
            ],
            [
                'name'=>'Settings',
                'icon'=>'fas fa-cogs',
                'route'=>'settings.index',
                'order'=>6,
                'status'=>1,
                'permission_name'=>'settings'
            ],
        ];

        foreach ($menus as $menuData){
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            $menu = Menu::create($menuData);

            foreach($children as $child){
                $child['parent_id'] = $menu->id;
                Menu::create($child);
            }
        }
    }
}