<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SubscriptionsSeeder::class,
            LanguagesSeeder::class,
            // CategorySeeder::class,
            AdminSettingSeeder::class,
            IngredientSeeder::class,
            CountrySeeder::class,
            CodePageSeeder::class,
            TimeZoneSeeder::class,
        ]);
    }
}
