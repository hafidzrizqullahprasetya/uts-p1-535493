<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(): JsonResponse
    {
        $assets = Asset::orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Daftar aset berhasil diambil',
            'data' => $assets,
        ], 200);
    }

    public function show(Asset $asset): JsonResponse
    {
        return response()->json([
            'message' => 'Detail aset berhasil diambil',
            'data' => $asset,
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assets,code',
            'category' => 'required|string|max:100',
            'status' => 'required|string|in:tersedia,dipinjam,rusak,perawatan',
            'price' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        $asset = Asset::create($validated);

        return response()->json([
            'message' => 'Aset berhasil dibuat',
            'data' => $asset,
        ], 201);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assets,code,'.$asset->id,
            'category' => 'required|string|max:100',
            'status' => 'required|string|in:tersedia,dipinjam,rusak,perawatan',
            'price' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        $asset->update($validated);

        return response()->json([
            'message' => 'Aset berhasil diperbarui',
            'data' => $asset,
        ], 200);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'message' => 'Aset berhasil dihapus',
        ], 200);
    }
}
