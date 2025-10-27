<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{

    public function storeFile(Request $request)
    {
        if (!$request->hasFile('fileholder_files')) {
            return response()->json([
                'status' => false,
                'error' => __("Something went wrong! Please try again."),
                'file_info' => null,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fileholder_files' => 'required|mimes:' . $request->mimes,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->all(),
                'file_info' => null,
            ], 400);
        }

        $validated = $validator->safe()->all();

        /** @var \Illuminate\Http\UploadedFile $fileHolder */
        $fileHolder = $validated['fileholder_files'];
        $fileExtension = $fileHolder->getClientOriginalExtension();
        $storedFileName = Str::uuid() . "." . $fileExtension;
        $fileDirectory = public_path('fileholder/img');

        $fileInfo = [
            'path' => asset('public/fileholder/img/'),
            'file_name' => $storedFileName,
            'file_link' => asset('public/fileholder/img/' . $storedFileName),
            'file_type' => $fileHolder->getClientMimeType(),
            'file_old_name' => $fileHolder->getClientOriginalName(),
        ];

        try {
            if (! File::exists($fileDirectory)) {
                File::makeDirectory($fileDirectory, 0755, true);
            }

            $fileHolder->move($fileDirectory, $storedFileName);
            chmod($fileDirectory . '/' . $storedFileName, 0644);
        } catch (Exception $e) {
            Log::error('File upload failed', ['exception' => $e]);

            return response()->json([
                'status' => false,
                'error' => __("Something went wrong! Please try again."),
                'file_info' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => __("File Uploaded Successfully!"),
            'file_info' => $fileInfo,
        ]);
    }

    public function removeFile(Request $request) {
        $validator = Validator::make($request->all(),[
            'file_info' => 'required|json',
        ]);

        if($validator->fails()) {
            $data['error']  = $validator->errors()->all();
            $data['status'] = false;
            return response()->json($data,400);
        }

        $validated = $validator->safe()->all();

        $file_path = '/fileholder/img';

        $file_info = json_decode($validated['file_info']);
        $data['status'] = true;
        try {
            FIle::delete(public_path($file_path.'/'.$file_info->file_name));
            $data['message'] = __("File Deleted Successfully!");
        }catch(Exception $e) {
            $data['status'] = false;
            $data['error'] = $e;
            $data['message'] = __("Something went wrong! Please try again.");
        }

        $data['file_info'] = $file_info;

        return response()->json($data,200);

    }
}
