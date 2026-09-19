<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Adjust if using an Admin model
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(['email' => 'ashokk96jangid@gmail.com'], [
            'name' => 'Ashok Jangid',
            'password' => Hash::make('Ashok96j'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->call(RbacSeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(CarModelSeeder::class);
        $this->call(VariantSeeder::class);
        $this->call(SpecificationSeeder::class);
    }
}
