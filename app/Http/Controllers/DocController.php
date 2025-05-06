<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DocController extends Controller
{
    public function index()
    {
        return view('docs.index');
    }

    public function getApiDocs()
    {
        $path = storage_path('api-docs/api-docs.json');
        return response()->json(json_decode(File::get($path), true));
    }
}
