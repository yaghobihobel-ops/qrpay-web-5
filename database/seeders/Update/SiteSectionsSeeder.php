<?php

namespace Database\Seeders\Update;

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
        $site_sections = file_get_contents(base_path("database/seeders/Update/site-section.json"));
        $data = SiteSections::where('key','pricing-section')->first();
        if(!$data){
            SiteSections::insert(json_decode($site_sections,true));
        }

    }
}
