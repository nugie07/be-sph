<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|file|max:10240', // max 10MB
    ]);

    $folder = $request->input('folder');
    $path = $request->file('file')->store(
        $folder, // folder dalam bucket (opsional)
        'byteplus' // disk name
    );

    return response()->json(['path' => $path]);
}
}
