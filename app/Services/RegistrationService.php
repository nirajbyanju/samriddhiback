<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use App\Models\RoleUser;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\DB;

class RegistrationService
{

    // public function registerUser(array $data)
    // {
    //     // Generate a user code
    //     $currentYear = now()->year;
    //     $latestId = User::max('id') + 1;
    //     $userCode = "Opsh-{$currentYear}-{$latestId}";

    //     // Map the input fields to the database columns
    //     $mappedData = [
    //        'name' => $data['name'],
    //         'email' => $data['email'],
    //         'phone' => $data['phone'],
    //         'password' => Hash::make($data['password']),
    //     ];

    //     // Create the user
    //     $user = User::create($mappedData);
    //     // UserDetail::create(['user_id' => $user->id]);
    //     $user->roles()->attach(5);


    //     // Dispatch the events
    //     // UserRegistered::dispatch($user);
    //     // $user->notify(new UserNotification("New user has been registered as {$user->first_name} {$user->last_name}"));
    //     $user->sendEmailVerificationNotification();

    //     // Return the token and user details
    //     $token = $user->createToken('MyApp')->plainTextToken;
    //     return [
    //         'token' => $token,
    //         'name' => $user->first_name . ' ' . $user->last_name,
    //     ];
    // }

    public function registerUser(array $data)
    {
        DB::beginTransaction();

        try {

            // Generate a user code
            $currentYear = now()->year;
            $latestId = User::max('id') + 1;
            $userCode = "Opsh-{$currentYear}-{$latestId}";

            $nameParts = explode(' ', trim($data['name']));

            $firstName = $nameParts[0] ?? null;
            $lastName = count($nameParts) > 1 ? end($nameParts) : null;

            // Middle name (everything except first and last)
            if (count($nameParts) > 2) {
                $middleName = implode(' ', array_slice($nameParts, 1, -1));
            } else {
                $middleName = null;
            }
            $baseUsername = strtolower(str_replace(' ', '', $data['name']));
            $username = $baseUsername;
            $counter = 1;

            // Check uniqueness
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $mappedData = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ];

            // Create User
            $user = User::create($mappedData);
            UserDetail::create(['user_id' => $user->id]);

            // ✅ Assign role properly (DO NOT use hardcoded ID)
            $user->assignRole('user'); // make sure this role exists

            // Send email verification
            // $user->sendEmailVerificationNotification();

            // Create token
            $token = $user->createToken('MyApp')->plainTextToken;

            DB::commit();

            return [
                'token' => $token,
                'name' => $user->name,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function registerAdmin(array $data)
    {
        DB::beginTransaction();

        try {

            // Generate a user code
            $currentYear = now()->year;
            $latestId = User::max('id') + 1;
            $userCode = "Opsh-{$currentYear}-{$latestId}";

            $mappedData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ];

            // Create User
            $user = User::create($mappedData);
            UserDetail::create(['user_id' => $user->id]);

            // ✅ Assign role properly (DO NOT use hardcoded ID)
            $user->assignRole('admin'); // make sure this role exists

            // Send email verification
            // $user->sendEmailVerificationNotification();

            // Create token
            $token = $user->createToken('MyApp')->plainTextToken;

            DB::commit();

            return [
                'token' => $token,
                'name' => $user->name,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
