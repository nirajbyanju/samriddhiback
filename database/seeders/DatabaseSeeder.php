<?php

namespace Database\Seeders;

use App\Models\User;
use DeepCopy\f013\C;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str; // <-- Import Str

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            PermissionMatrixSeeder::class,

            ConstructionStatusSeeder::class,
            FurnishingSeeder::class,
            HouseDetailSeeder::class,
            HouseTypeSeeder::class,
            ListingTypeSeeder::class,
            NepalLocationSeeder::class,
            PropertyFaceSeeder::class,
            PropertyStatusSeeder::class,
            PropertyTypeSeeder::class,
            RoadConditionSeeder::class,
            RoadTypeSeeder::class,
            RoofTypeSeeder::class,
            SewageTypeSeeder::class,
            UnitSeeder::class,
            WaterSourceSeeder::class,
            MeasureUnitsSeeder::class,
            PropertyCategorySeeder::class,
            ParkingTypeSeeder::class,
            AmenitiesSeeder::class,
            ContactMethodSeeder::class,
            FollowupStatusSeeder::class,
            HouseMaterialsSeeder::class,
            ContentMethodSeeder::class,
            RequestTypeSeeder::class,
            StatusSeeder::class,
            CategorySeeder::class



        ]);
    }
}
