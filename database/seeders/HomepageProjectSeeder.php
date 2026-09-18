<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageProjectSeeder extends Seeder
{
    /**
     * Import exactly the ten first-set homepage cards verbatim from
     * resources/views/home.blade.php:240-364 (Ruling R2).
     *
     * - Upserts only by the immutable `migration_key`
     *   (`homepage-legacy-01`–`10`); never matches on editable title/image
     *   text, so re-running repairs an edited card without adding an
     *   eleventh row.
     * - Sets `sort_order` 1–10 in current card order and publishes all ten.
     * - Preserves the existing `/static/images/...` paths initially; the
     *   Task 4 image URL resolver supports both these static paths and
     *   later public-disk paths.
     * - `created_at` is insert-only so reruns keep the original stamp while
     *   `updated_at` refreshes via the upsert update list.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'migration_key' => 'homepage-legacy-01',
                'tag' => 'RENEWABLE ENERGY INFRASTRUCTURE',
                'image' => '/static/images/steel1.jpg',
                'location' => 'TIWI, ALBAY & BATANGAS–LAGUNA',
                'title' => 'Tiwi & Mak-ban Geothermal Powerplant',
                'info' => 'Supplied robust steel products that supported the construction and operational requirements of these major geothermal facilities, which harness the Earth’s natural heat to generate reliable and renewable electricity for the Luzon grid.',
                'is_published' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-02',
                'tag' => 'Commercial Building',
                'image' => '/static/images/steel3.jpg',
                'location' => 'Pasay City, Metro Manila',
                'title' => 'SM Mall of Asia',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ major shopping, leisure, and entertainment destinations.',
                'is_published' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-03',
                'tag' => 'HOTEL & INTEGRATED RESORT',
                'image' => '/static/images/steel2.jpg',
                'location' => 'PARAÑAQUE CITY, METRO MANILA',
                'title' => 'OKADA MANILA',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of this 30-hectare luxury integrated resort, featuring hotels, dining, retail, entertainment, and event facilities.',
                'is_published' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-04',
                'tag' => 'CONVENTION & EXHIBITION CENTER',
                'image' => '/static/images/steel4.jpg',
                'location' => 'PASAY CITY, METRO MANILA',
                'title' => 'SMX CONVENTION CENTER MANILA',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ leading venues for conventions, exhibitions, corporate functions, and large-scale events.',
                'is_published' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-05',
                'tag' => 'TRANSPORTATION INFRASTRUCTURE',
                'image' => '/static/images/steel6.jpg',
                'location' => 'METRO MANILA TO BACOOR, CAVITE',
                'title' => 'LRT-1 CAVITE EXTENSION',
                'info' => 'Contributed quality steel materials to the development of this major railway expansion, designed to improve public transportation and provide faster, more convenient travel between Metro Manila and Cavite.',
                'is_published' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-06',
                'tag' => 'HOTEL & HOSPITALITY',
                'image' => '/static/images/Manila-hotel.jpg',
                'location' => 'ERMITA, MANILA',
                'title' => 'THE MANILA HOTEL',
                'info' => 'Provided dependable steel materials for the construction and improvement requirements of this historic luxury hotel, helping preserve its distinguished character while supporting modern hospitality operations.',
                'is_published' => true,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-07',
                'tag' => 'INTEGRATED RESORT & ENTERTAINMENT COMPLEX',
                'image' => '/static/images/steel5.jpg',
                'location' => 'PASAY CITY, METRO MANILA',
                'title' => 'NEWPORT WORLD RESORTS',
                'info' => 'Delivered reliable steel materials that contributed to the development of this landmark destination, bringing together luxury hotels, dining establishments, retail spaces, entertainment venues, and event facilities within one integrated complex.',
                'is_published' => true,
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-08',
                'tag' => 'COMMERCIAL & RETAIL DEVELOPMENT',
                'image' => '/static/images/SM-SAN LAZARO.jpg',
                'location' => 'SANTA CRUZ, MANILA',
                'title' => 'SM CITY SAN LAZARO',
                'info' => 'Supported the development of this major urban shopping destination through the supply of dependable steel materials for its structural and construction needs.',
                'is_published' => true,
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-09',
                'tag' => 'POWER & ENERGY INFRASTRUCTURE',
                'image' => '/static/images/GN-POWERPLANT.jpg',
                'location' => 'MARIVELES, BATAAN',
                'title' => 'GN POWER MARIVELES ENERGY CENTER',
                'info' => 'Furnished durable steel products that supported the development of this large-scale energy facility, built to strengthen power generation capacity and help meet the growing electricity requirements of the Luzon grid.',
                'is_published' => true,
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'migration_key' => 'homepage-legacy-10',
                'tag' => 'ROAD & TRANSPORTATION INFRASTRUCTURE',
                'image' => '/static/images/NLEX-.jpg',
                'location' => 'METRO MANILA TO CENTRAL AND NORTHERN LUZON',
                'title' => 'NORTH LUZON EXPRESSWAY',
                'info' => 'Contributed durable steel products for the construction and improvement of this major expressway, helping support safer roadways, smoother mobility, and more efficient movement of people and goods between Metro Manila and Northern Luzon.',
                'is_published' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('projects')->upsert($rows, ['migration_key'], [
            'tag',
            'image',
            'location',
            'title',
            'info',
            'is_published',
            'sort_order',
            'updated_at',
        ]);
    }
}
