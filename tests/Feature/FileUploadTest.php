<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    public function test_file_upload_succeeds()
    {
        $publicPath = storage_path('framework/testing/public-success');
        $originalPublicPath = public_path();

        File::deleteDirectory($publicPath);
        File::makeDirectory($publicPath . '/fileholder/img', 0755, true);

        $this->app->instance('path.public', $publicPath);

        try {
            $file = UploadedFile::fake()->image('avatar.png');

            $response = $this->post('/fileholder-upload', [
                'fileholder_files' => $file,
                'mimes' => 'png',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                ])
                ->assertJsonStructure([
                    'status',
                    'message',
                    'file_info' => [
                        'path',
                        'file_name',
                        'file_link',
                        'file_type',
                        'file_old_name',
                    ],
                ]);

            $data = $response->json();
            $this->assertFileExists($publicPath . '/fileholder/img/' . $data['file_info']['file_name']);
        } finally {
            $this->app->instance('path.public', $originalPublicPath);
            File::deleteDirectory($publicPath);
        }
    }

    public function test_file_upload_handles_failure_gracefully()
    {
        Log::spy();

        $publicPath = storage_path('framework/testing/public-error');
        $originalPublicPath = public_path();

        File::deleteDirectory($publicPath);
        File::makeDirectory($publicPath . '/fileholder/img', 0555, true);

        $this->app->instance('path.public', $publicPath);

        try {
            $file = UploadedFile::fake()->image('avatar.png');

            $response = $this->post('/fileholder-upload', [
                'fileholder_files' => $file,
                'mimes' => 'png',
            ]);

            $response->assertStatus(500)
                ->assertJson([
                    'status' => false,
                    'file_info' => null,
                ])
                ->assertJsonStructure([
                    'status',
                    'error',
                    'file_info',
                ]);

            Log::shouldHaveReceived('error')->once();
        } finally {
            chmod($publicPath . '/fileholder/img', 0755);
            $this->app->instance('path.public', $originalPublicPath);
            File::deleteDirectory($publicPath);
        }
    }
}

