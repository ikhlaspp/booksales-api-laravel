<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Kredensial tidak valid'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'role' => $user->role,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return response()->json($request->user()->fresh());
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'Password saat ini tidak sesuai.'], 422);
        }

        $request->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
