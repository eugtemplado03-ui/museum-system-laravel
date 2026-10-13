<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected function getJwtSecret(): string
    {
        return config('app.jwt_secret', env('JWT_SECRET', 'change-this-secret-in-production'));
    }

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password) {
            return response()->json(['error' => 'Username and password are required.'], 400);
        }

        $user = User::where('username', $username)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Incorrect username or password.'], 401);
        }

        $secret = $this->getJwtSecret();
        $payload = [
            'sub' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'exp' => time() + (12 * 3600), // 12 hours
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $currentPassword = $request->input('currentPassword');
        $newPassword = $request->input('newPassword');
        $confirmPassword = $request->input('confirmPassword');

        if (!is_string($currentPassword) || empty($currentPassword) || strlen($currentPassword) > 128) {
            return response()->json(['error' => 'Enter your current password.'], 400);
        }
        if (!is_string($newPassword) || strlen($newPassword) < 12 || strlen($newPassword) > 128) {
            return response()->json(['error' => 'The new password must be between 12 and 128 characters.'], 400);
        }
        if ($newPassword !== $confirmPassword) {
            return response()->json(['error' => 'New passwords do not match.'], 400);
        }

        $jwtUser = $request->attributes->get('jwt_user');
        $user = User::find($jwtUser['sub'] ?? null);

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Please sign in again and retry.'], 401);
        }

        if (!Hash::check($currentPassword, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect.'], 400);
        }

        if (Hash::check($newPassword, $user->password)) {
            return response()->json(['error' => 'Choose a new password different from your current one.'], 400);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->attributes->get('jwt_user'),
        ]);
    }
}
