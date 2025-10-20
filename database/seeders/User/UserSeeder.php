<?php

namespace Database\Seeders\User;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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
                'firstname'         => "Test",
                'lastname'          => "User",
                'email'             => "user@appdevs.net",
                'username'          => "appdevs",
                'mobile_code'       => "880",
                'mobile'            => "123456789",
                'full_mobile'       => "880123456789",
                'status'            => true,
                'password'          => Hash::make("appdevs"),
                'address'           => '{"country":"Bangladesh","city":"Dhaka","zip":"1230","state":"Bangladesh","address":"Dhaka,Bangladesh"}',
                'email_verified'    => true,
                'sms_verified'      => true,
                'kyc_verified'      => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'firstname'         => "Test",
                'lastname'          => "User2",
                'email'             => "user2@appdevs.net",
                'username'          => "testusr2",
                'mobile_code'       => "880",
                'mobile'            => "123456781",
                'full_mobile'       => "880123456781",
                'status'            => true,
                'password'          => Hash::make("appdevs"),
                'address'           => '{"country":"Bangladesh","city":"Dhaka","zip":"1230","state":"Bangladesh","address":"Dhaka,Bangladesh"}',
                'email_verified'    => true,
                'sms_verified'      => true,
                'kyc_verified'      => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

        ];

        User::insert($data);
    }
}
