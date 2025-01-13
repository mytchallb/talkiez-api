<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\User;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password'])
            ]);

            return response()->json([
                'message' => 'User registered successfully',
                'token' => $user->createToken('auth_token')->plainTextToken
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {

        // make sure email and password are provided
        if (!$request->email || !$request->password) {
            return response()->json(['message' => 'Email and password are required'], 400);
        }
        // login user, verify password and email and return token
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        return response()->json(['token' => $user->createToken('auth_token')->plainTextToken]);
    }

    public function logout(Request $request)
    {
        try {
            // logout user
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out']);
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }

}
