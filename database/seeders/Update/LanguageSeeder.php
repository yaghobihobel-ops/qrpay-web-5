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
        $candidates = [
            [
                'name' => 'Hindi',
                'code' => 'hi',
                'dir'  => 'ltr',
            ],
            [
                'name' => 'Persian',
                'code' => 'fa',
                'dir'  => 'rtl',
            ],
            [
                'name' => 'Chinese',
                'code' => 'zh',
                'dir'  => 'ltr',
            ],
            [
                'name' => 'Russian',
                'code' => 'ru',
                'dir'  => 'ltr',
            ],
            [
                'name' => 'Turkish',
                'code' => 'tr',
                'dir'  => 'ltr',
            ],
            [
                'name' => 'Pashto',
                'code' => 'ps',
                'dir'  => 'rtl',
            ],
        ];

        $records = [];

        foreach ($candidates as $language) {
            if (Language::where('code', $language['code'])->exists()) {
                continue;
            }

            $records[] = array_merge($language, [
                'status' => 0,
                'last_edit_by' => 1,
                'created_at' => now(),
            ]);
        }

        if (! empty($records)) {
            Language::insert($records);
        }

    }
}
