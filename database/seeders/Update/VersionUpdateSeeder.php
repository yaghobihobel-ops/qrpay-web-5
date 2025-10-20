<?php

namespace Database\Seeders\Update;

use Database\Seeders\Admin\HeaderSectionSeeder;
use Database\Seeders\Admin\LiveExchangeRateSeeder;
use Database\Seeders\Admin\SetupPageSeeder;
use Illuminate\Database\Seeder;

class VersionUpdateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //version Update Seeders
        $this->call([
            SiteSectionsSeeder::class,
            HeaderSectionSeeder::class,
            SetupPageSeeder::class,
            AppSettingsSeeder::class,
            LanguageSeeder::class,
            BasicSettingsSeeder::class,
            ExtensionSeeder::class,
            LiveExchangeRateSeeder::class,
        ]);



    }
}
