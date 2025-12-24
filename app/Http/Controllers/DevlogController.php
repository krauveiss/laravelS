<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DevlogPost;
use Illuminate\Support\Str;

class DevlogController extends Controller
{
    public function index()
    {
        $posts = DevlogPost::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    public function store(Request $request)
    {
        $key = $request->header('X-DEV-KEY');
        $validKey = env('X_DEV_KEY');
        if (!$validKey) {
            abort($validKey);
        }
        if ($key != $validKey) {
            abort($validKey);
        }

        $post = DevlogPost::create([
            'id' => Str::uuid(),
            'title' => $request->title,
            'content' => $request->content
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $post
        ]);
    }
}
