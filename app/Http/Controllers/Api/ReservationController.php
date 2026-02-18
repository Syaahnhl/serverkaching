<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation; // [FIX] Gunakan Model
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Carbon\Carbon;

class ReservationController extends Controller
{
    // 1. API: SIMPAN DATA DARI ANDROID
    public function store(Request $request)
    {
        try {
            // Validasi
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string',
                'date' => 'required|date',    // Format: YYYY-MM-DD
                'time' => 'required|string',  // Format: HH:MM
                'pax' => 'required|integer',  // Jumlah orang
                'phone_number' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            // [SaaS] Simpan Data dengan User ID
            $reservation = Reservation::create([
                'user_id' => Auth::id(), // <--- KUNCI SAAS
                'customer_name' => $request->customer_name,
                'phone_number' => $request->phone_number,
                'date' => $request->date,
                'time' => $request->time,
                'pax' => $request->pax,
                'notes' => $request->notes,
                'status' => 'Pending', // Default status
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Reservasi Berhasil Disimpan!', 
                'data' => $reservation
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. API: LIHAT DAFTAR BOOKING (Untuk Android)
    public function index()
    {
        $userId = Auth::id();

        // [FIX] Hapus filter tanggal sementara agar semua data muncul di HP A
        // Kita hanya filter berdasarkan User ID pemilik resto.
        $reservations = Reservation::where('user_id', $userId)
            ->orderBy('date', 'desc') // Urutkan dari yang terbaru (biar kelihatan inputan baru)
            ->orderBy('time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reservations 
        ], 200);
    }
    
    // 3. API: UPDATE STATUS (Misal: Tamu Datang / Batal)
    public function updateStatus(Request $request, $id)
    {
        $reservation = Reservation::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->first();

        if (!$reservation) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $reservation->update(['status' => $request->status]); // Status: Seated, Cancelled, Done

        return response()->json(['status' => 'success', 'message' => 'Status updated']);
    }

    // [BARU] 4. API: HAPUS RESERVASI
    public function destroy($id)
    {
        $reservation = Reservation::where('id', $id)
                        ->where('user_id', Auth::id()) // Pastikan hanya bisa hapus punya sendiri
                        ->first();

        if (!$reservation) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $reservation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservasi berhasil dihapus'
        ], 200);
    }

    public function updateNotes(Request $request, $id)
    {
        // 1. Cari Data
        $reservation = Reservation::where('id', $id)
                        ->where('user_id', Auth::id()) // Security check
                        ->first();

        if (!$reservation) {
            return response()->json(['status' => 'error', 'message' => 'Reservasi tidak ditemukan'], 404);
        }

        // 2. Validasi (Boleh string kosong jika menu dihapus semua)
        // Kita gunakan 'nullable' agar tidak error jika dikirim string kosong
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        // 3. Update Kolom Notes
        $reservation->notes = $request->notes;
        $reservation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan berhasil diperbarui',
            'data' => $reservation
        ], 200);
    }
}