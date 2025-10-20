<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\SiteSections;
use Illuminate\Database\Seeder;

class SiteSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $site_sections = file_get_contents(base_path("database/seeders/Admin/site-section.json"));
        SiteSections::truncate();
        SiteSections::insert(json_decode($site_sections,true));
    }
}
