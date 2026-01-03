<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek Kredensial
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            
            // Jika Sukses
            $user = Auth::user();
            
            // Hapus semua token lama (opsional, biar aman)
            // $user->tokens()->delete(); 
            
            // Buat Token Baru (Jika pakai Sanctum)
            // $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? 'cashier', // Pastikan ada kolom role atau default cashier
                    // 'token' => $token // Uncomment jika pakai Sanctum
                ]
            ], 200);
        } else {
            // Jika Gagal
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau Password salah'
            ], 401);
        }
    }
    
    // Fitur Logout (Opsional)
    public function logout(Request $request)
    {
        // Auth::user()->tokens()->delete(); // Jika pakai Sanctum
        return response()->json(['message' => 'Logged out']);
    }
}