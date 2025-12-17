<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('api')->plainTextToken;
        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'invalid credentials'
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([], 200);
    }
}
