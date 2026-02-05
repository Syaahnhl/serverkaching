<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Setting; // [PENTING] Import Model Setting
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // --- 1. LOGIN ---
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                // 1. [BARU] Generate Token
                $token = $user->createToken('auth_token')->plainTextToken;

                // [UPDATE LOGIC SAAS]
                $hasOutlet = Setting::where('user_id', $user->id)
                                ->whereNotNull('store_name')
                                ->exists();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Login berhasil',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ?? 'cashier',
                        'has_outlet' => $hasOutlet,
                        'token' => $token // [BARU] Kirim token ini
                    ]
                ], 200);
            } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau Password salah'
            ], 401);
        }
    }

    // --- 2. REGISTER ---
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $otpCode = rand(1000, 9999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otpCode,
        ]);

        // Kirim OTP via JSON (Debug)
        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil. Silakan cek OTP.',
            'data' => $user,
            'debug_otp' => $otpCode
        ], 201);
    }

    // --- 3. VERIFIKASI OTP ---
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak lengkap'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp != $request->otp) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Kode OTP Salah atau Email tidak ditemukan'
            ], 400);
        }

        $user->otp = null;
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi Berhasil! Akun aktif.',
            'data' => $user
        ], 200);
    }

    // --- 4. LOGOUT ---
    public function logout(Request $request)
    {
        // Jika pakai Sanctum: $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}