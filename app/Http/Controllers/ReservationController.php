<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
                'date' => 'required|date',    // Server minta "YYYY-MM-DD"
                'time' => 'required|string',
                'pax' => 'required|integer',  // Server minta Angka
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            // Simpan
            $id = DB::table('reservations')->insertGetId([
                'customer_name' => $request->customer_name,
                'phone_number' => $request->phone_number,
                'date' => $request->date,
                'time' => $request->time,
                'pax' => $request->pax,
                'notes' => $request->notes,
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Reservasi Berhasil Disimpan!', 
                'server_id' => $id
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. WEB: LIHAT DAFTAR BOOKING
    public function index()
    {
        // Ambil reservasi mulai hari ini ke depan
        $reservations = DB::table('reservations')
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        return view('reservations.index', ['reservations' => $reservations]);
    }
}