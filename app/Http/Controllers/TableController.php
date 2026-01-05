<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    // API: Ambil Semua Meja (Untuk Android)
    public function index()
    {
        $tables = DB::table('tables')->orderBy('id', 'asc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $tables
        ], 200);
    }

    // (Opsional) API: Set Status Meja
    public function updateStatus(Request $request, $id)
    {
        DB::table('tables')->where('id', $id)->update(['is_occupied' => $request->is_occupied]);
        return response()->json(['status' => 'success']);
    }
}