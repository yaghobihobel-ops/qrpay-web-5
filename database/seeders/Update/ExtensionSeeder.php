<?php

namespace Database\Seeders\Update;

use App\Constants\ExtensionConst;
use App\Models\Admin\Extension;
use Illuminate\Database\Seeder;

class ExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name'              => "Google Recaptcha",
                'slug'              => ExtensionConst::GOOGLE_RECAPTCHA_SLUG,
                'description'       => "Google Recaptcha",
                'script'            => null,
                'shortcode'         =>  json_encode([
                                            "site_key" => [
                                                'title' => 'Site key',
                                                'value' => '6Le-UYwqAAAAAG3Sk_Zmeg_l3gFWTUfKw23LrdEL'
                                            ],
                                            "secret_key" => [
                                                'title' => 'Secret Key',
                                                'value' => '6Le-UYwqAAAAACO09Kom_RSdSpDasYomBsEU3hE1'
                                            ]
                                        ]),

                'support_image'     => "recaptcha.png",
                'image'             => "recaptcha3.png",
                'status'            => true,
                'created_at'        => now(),
            ],
        ];
        if(!Extension::where('slug',ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->exists()){
            Extension::insert($data);
        }

    }
}
