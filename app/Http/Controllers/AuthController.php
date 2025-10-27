<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:255',
            'email_address' => 'required|email|unique:users_tbl,email_address',
            'role' => 'required|in:buyer,seller',
            'shop_name' => 'nullable|string|max:255',
            'shop_tagline' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create a new user entry
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password), // Hash password
            'full_name' => $request->full_name,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
            'email_address' => $request->email_address,
            'role' => $request->role,
            'shop_name' => $request->role === 'seller' ? $request->shop_name : null,
            'image' => $request->image,
        ]);

        $token = $user->createToken('register-token')->plainTextToken;

        return response()->json([
          'message' => 'User registered successfully!',
          'token'   => $token,
          'user'    => [
             'user_id'  => $user->user_id,
             'username' => $user->username,
             'role'     => $user->role,
          ],
        ], 201);

        return response()->json(['message' => 'User registered successfully!'], 200);
    }


    public function login(Request $request)
{
    $credentials = [
        'username' => 'admin123@gmail.com',
        'password' => 'admin123',
    ];

    $user = User::where('username', $credentials['username'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('login-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'role' => $user->role,
        ],
    ]);
}

    public function logout(Request $request)
    {
      $request->user()->currentAccessToken()->delete();
  
      return response()->json([
        'message' => 'Logged out'
      ], 200);
    }
}
