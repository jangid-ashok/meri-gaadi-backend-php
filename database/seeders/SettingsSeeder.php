<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'site_name', 'type' => 'string', 'value' => 'Meri Gaadi', 'description' => 'Brand or site name shown in the app.', 'is_public' => true],
            ['group' => 'general', 'key' => 'site_description', 'type' => 'text', 'value' => 'Your trusted destination for car research, buying advice, and automotive information.', 'description' => 'Short summary about the website.', 'is_public' => true],
            ['group' => 'general', 'key' => 'contact_email', 'type' => 'string', 'value' => 'hello@merigaadi.com', 'description' => 'Primary contact email address.', 'is_public' => true],
            ['group' => 'general', 'key' => 'contact_phone', 'type' => 'string', 'value' => '+91 98765 43210', 'description' => 'Primary contact phone number.', 'is_public' => true],
            ['group' => 'general', 'key' => 'default_country', 'type' => 'string', 'value' => 'India', 'description' => 'Default country used for market pages.', 'is_public' => true],
            ['group' => 'general', 'key' => 'default_timezone', 'type' => 'string', 'value' => 'Asia/Kolkata', 'description' => 'Default timezone for scheduling and display.', 'is_public' => true],
            ['group' => 'social', 'key' => 'facebook_url', 'type' => 'url', 'value' => 'https://facebook.com/merigaadi', 'description' => 'Facebook page URL.', 'is_public' => true],
            ['group' => 'social', 'key' => 'instagram_url', 'type' => 'url', 'value' => 'https://instagram.com/merigaadi', 'description' => 'Instagram profile URL.', 'is_public' => true],
            ['group' => 'social', 'key' => 'youtube_url', 'type' => 'url', 'value' => 'https://youtube.com/@merigaadi', 'description' => 'YouTube channel URL.', 'is_public' => true],
            ['group' => 'social', 'key' => 'linkedin_url', 'type' => 'url', 'value' => 'https://linkedin.com/company/merigaadi', 'description' => 'LinkedIn company profile.', 'is_public' => true],
            ['group' => 'social', 'key' => 'twitter_url', 'type' => 'url', 'value' => 'https://x.com/merigaadi', 'description' => 'X/Twitter profile URL.', 'is_public' => true],
            ['group' => 'system', 'key' => 'default_pagination', 'type' => 'integer', 'value' => '15', 'description' => 'Default number of rows per page for lists.', 'is_public' => false],
            ['group' => 'system', 'key' => 'maintenance_mode', 'type' => 'boolean', 'value' => '0', 'description' => 'Turns the public site into maintenance mode.', 'is_public' => false],
            ['group' => 'system', 'key' => 'date_format', 'type' => 'string', 'value' => 'd M Y', 'description' => 'Frontend date format.', 'is_public' => true],
            ['group' => 'system', 'key' => 'time_format', 'type' => 'string', 'value' => 'h:i A', 'description' => 'Frontend time format.', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
