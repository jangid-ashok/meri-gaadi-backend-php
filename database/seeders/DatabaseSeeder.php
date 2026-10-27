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
        $adminEmail = trim((string) env('ADMIN_EMAIL', ''));
        $adminPassword = (string) env('ADMIN_PASSWORD', '');

        if ($adminEmail !== '' || $adminPassword !== '') {
            if ($adminEmail === '' || strlen($adminPassword) < 16) {
                throw new \InvalidArgumentException('Set both ADMIN_EMAIL and an ADMIN_PASSWORD of at least 16 characters to provision the initial admin.');
            }

            User::updateOrCreate(['email' => $adminEmail], [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $this->call(RbacSeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(CarModelSeeder::class);
        $this->call(VariantSeeder::class);
        $this->call(SpecificationSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(TagSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(ArticleSeeder::class);
        $this->call(HomepageSectionSeeder::class);
    }
}
