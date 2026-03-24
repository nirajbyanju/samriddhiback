<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'userCode' => 'SM-2026-1',
            'first_name' => 'Niraj',
            'last_name' => 'Byanju',
            'username' => 'nirajbyanju',
            'email' => 'nirajbyanju1234@gmail.com',
            'email_verified_at' => now(),
            'phone' => '+9779800000000',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10)
        ]);
        $admin->assignRole('Admin');

        $manager = User::create([
            'userCode' => 'SM-2026-2',
            'first_name' => 'Manager',
            'last_name' => 'User',
            'username' => 'manageruser',
            'email' => 'manager@example.com',
            'email_verified_at' => now(),
            'phone' => '+9779800000001',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10)
        ]);
        $manager->assignRole('Manager');

        $employee = User::create([
            'userCode' => 'SM-2026-3',
            'first_name' => 'Employee',
            'last_name' => 'User',
            'username' => 'employeeuser',
            'email' => 'employee@example.com',
            'email_verified_at' => now(),
            'phone' => '+9779800000002',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10)
        ]);
        $employee->assignRole('Employee');
    }
}