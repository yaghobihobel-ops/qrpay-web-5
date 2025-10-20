<?php

namespace Database\Seeders\Update;

use App\Models\Admin\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()

    {
        removeLangJson();
        $data = [

            [
                'name'          => "Hindi",
                'code'          => "hi",
                'status'        => 0,
                'last_edit_by'  => 1,
                'dir'           => 'ltr',
                'created_at'    => now(),

            ]
        ];
        if(!Language::where('code','hi')->exists()){
            Language::insert($data);
        }

    }
}
