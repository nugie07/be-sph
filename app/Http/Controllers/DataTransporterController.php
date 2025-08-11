<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataTransporter;

class DataTransporterController extends Controller
{
    // List all supplier & Vendor
    public function index(Request $request)
    {
        $query = DataTransporter::query();

        if ($request->has('category')) {
            if ($request->category == 1) {
                $query->where('category', 1);
            }
        } else {
            $query->where('category', 2);
        }

        $data = $query->orderBy('nama')->get();
        return response()->json(['data' => $data]);
    }

    // Store new transporter
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'pic' => 'required|string',
            'contact_no' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'status' => 'required|integer',
        ]);

        $transporter = DataTransporter::create($validated);

        return response()->json([
            'message' => 'Data transporter berhasil ditambahkan',
            'data' => $transporter
        ], 201);
    }

    // Update existing transporter
    public function update(Request $request, $id)
    {
        $transporter = DataTransporter::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string',
            'pic' => 'required|string',
            'contact_no' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'status' => 'required|integer',
        ]);

        $transporter->update($validated);

        return response()->json([
            'message' => 'Data transporter berhasil diperbarui',
            'data' => $transporter
        ]);
    }

    // Delete transporter
    public function destroy($id)
    {
        $transporter = DataTransporter::findOrFail($id);
        $transporter->delete();

        return response()->json([
            'message' => 'Data transporter berhasil dihapus'
        ]);
    }
}