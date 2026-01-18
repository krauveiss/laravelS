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

    public function show($id)
    {
        $post = DevlogPost::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'Post not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $post
        ]);
    }

    public function store(Request $request)
    {
        $key = $request->header('X-DEV-KEY');
        $validKey = config('dev.key');

        if (!$validKey) {
            abort(500);
        }

        if ($key !== $validKey) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = DevlogPost::create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $post,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $key = $request->header('X-DEV-KEY');
        $validKey = config('dev.key');

        if (!$validKey) {
            abort(500);
        }

        if ($key !== $validKey) {
            abort(403);
        }

        $post = DevlogPost::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'Post not found'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $post->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $post,
            'message' => 'Post updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $key = request()->header('X-DEV-KEY');
        $validKey = config('dev.key');

        if (!$validKey) {
            abort(500);
        }

        if ($key !== $validKey) {
            abort(403);
        }

        $post = DevlogPost::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'Post not found'
            ], 404);
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully'
        ]);
    }
}
