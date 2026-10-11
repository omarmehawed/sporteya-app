<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportsSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            [
                'name_ar' => 'كرة القدم',
                'name_en' => 'Football',
                'slug' => 'football',
                'icon' => 'sports-soccer',
                'default_players_per_side' => 5,
                'is_active' => true,
            ],
            [
                'name_ar' => 'بادل',
                'name_en' => 'Padel',
                'slug' => 'padel',
                'icon' => 'sports-tennis',
                'default_players_per_side' => 2,
                'is_active' => true,
            ],
            [
                'name_ar' => 'تنس',
                'name_en' => 'Tennis',
                'slug' => 'tennis',
                'icon' => 'sports-tennis',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'كرة السلة',
                'name_en' => 'Basketball',
                'slug' => 'basketball',
                'icon' => 'sports-basketball',
                'default_players_per_side' => 5,
                'is_active' => true,
            ],
            [
                'name_ar' => 'كرة الطائرة',
                'name_en' => 'Volleyball',
                'slug' => 'volleyball',
                'icon' => 'sports-volleyball',
                'default_players_per_side' => 6,
                'is_active' => true,
            ],
            [
                'name_ar' => 'اسكواش',
                'name_en' => 'Squash',
                'slug' => 'squash',
                'icon' => 'sports-tennis',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'سباحة',
                'name_en' => 'Swimming',
                'slug' => 'swimming',
                'icon' => 'pool',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'تنس طاولة',
                'name_en' => 'Table Tennis',
                'slug' => 'table-tennis',
                'icon' => 'sports-tennis',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'كرة اليد',
                'name_en' => 'Handball',
                'slug' => 'handball',
                'icon' => 'sports-handball',
                'default_players_per_side' => 7,
                'is_active' => true,
            ],
            [
                'name_ar' => 'ريشة طائرة',
                'name_en' => 'Badminton',
                'slug' => 'badminton',
                'icon' => 'sports-tennis',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'ملاكمة',
                'name_en' => 'Boxing',
                'slug' => 'boxing',
                'icon' => 'sports-mma',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'فنون قتالية',
                'name_en' => 'Martial Arts',
                'slug' => 'martial-arts',
                'icon' => 'sports-martial-arts',
                'default_players_per_side' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($sports as $sport) {
            DB::table('sports')->updateOrInsert(
                ['slug' => $sport['slug']],
                array_merge($sport, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
