<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{

    public function storeFile(Request $request) {
        if (! $request->hasFile('fileholder_files')) {
            return response()->json([
                'status' => false,
                'error' => __("Something went wrong! Please try again."),
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fileholder_files' => 'required|mimes:' . $request->mimes,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->all(),
            ], 422);
        }

        $fileHolder = $request->file('fileholder_files');
        $fileExtension = $fileHolder->getClientOriginalExtension();
        $storedFileName = Str::uuid() . '.' . $fileExtension;
        $destinationPath = public_path('fileholder/img');

        $responsePayload = [
            'status' => true,
            'path' => asset('public/fileholder/img/'),
            'file_name' => $storedFileName,
            'file_link' => asset('public/fileholder/img/' . $storedFileName),
            'file_type' => $fileHolder->getClientMimeType(),
            'file_old_name' => $fileHolder->getClientOriginalName(),
        ];

        try {
            if (! File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            $fileHolder->move($destinationPath, $storedFileName);
            chmod($destinationPath . '/' . $storedFileName, 0644);
        } catch (Exception $exception) {
            report($exception);

            return response()->json([
                'status' => false,
                'error' => __('File upload failed. Please try again.'),
            ], 500);
        }

        return response()->json($responsePayload, 200);
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
            File::delete(public_path($file_path.'/'.$file_info->file_name));
            $data['message'] = __("File Deleted Successfully!");
        }catch(Exception $e) {
            report($e);
            $data['status'] = false;
            $data['error'] = __('File deletion failed. Please try again.');
            $data['message'] = __("Something went wrong! Please try again.");
        }

        $data['file_info'] = $file_info;

        return response()->json($data, $data['status'] ? 200 : 500);

    }
}
