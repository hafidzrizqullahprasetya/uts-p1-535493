<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $assets = Asset::orderBy('id', 'desc')->get();

            return response()->json([
                'message' => 'Daftar aset berhasil diambil',
                'data' => $assets,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal menghubungi basis data saat mengambil daftar aset', [
                'siapa' => $request->user()?->email ?? 'pengguna',
                'sedang_mengerjakan' => 'mengambil_daftar_aset',
                'sebab' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal menghubungi basis data',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assets,code',
            'category' => 'required|string|max:100',
            'status' => 'required|string|in:tersedia,dipinjam,rusak,perawatan',
            'price' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi data aset gagal', [
                'siapa' => $request->user()?->email ?? 'pengguna',
                'sedang_mengerjakan' => 'validasi_penyimpanan_aset',
                'sebab' => 'Atribut masukan tidak memenuhi aturan validasi',
                'kolom_gagal' => array_keys($validator->errors()->toArray()),
            ]);

            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $asset = Asset::create($validator->validated());

            Log::info('Data aset berhasil disimpan', [
                'siapa' => $request->user()?->email ?? 'pengguna_terautentikasi',
                'sedang_mengerjakan' => 'menyimpan_data_aset',
                'sebab' => 'Penyimpanan data aset baru ke basis data selesai',
                'id_aset' => $asset->id,
                'kode_aset' => $asset->code,
                'kategori' => $asset->category,
            ]);

            return response()->json([
                'message' => 'Aset berhasil dibuat',
                'data' => $asset,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Gagal menghubungi basis data saat menyimpan aset', [
                'siapa' => $request->user()?->email ?? 'pengguna_terautentikasi',
                'sedang_mengerjakan' => 'menyimpan_data_aset',
                'sebab' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal menghubungi basis data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assets,code,'.$asset->id,
            'category' => 'required|string|max:100',
            'status' => 'required|string|in:tersedia,dipinjam,rusak,perawatan',
            'price' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi perbaruan data aset gagal', [
                'siapa' => $request->user()?->email ?? 'pengguna',
                'sedang_mengerjakan' => 'validasi_perbaruan_aset',
                'sebab' => 'Atribut perbaruan tidak memenuhi aturan validasi',
                'kolom_gagal' => array_keys($validator->errors()->toArray()),
            ]);

            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $asset->update($validator->validated());

            Log::info('Data aset berhasil diperbarui', [
                'siapa' => $request->user()?->email ?? 'pengguna_terautentikasi',
                'sedang_mengerjakan' => 'memperbarui_data_aset',
                'sebab' => 'Perubahan data aset berhasil diterapkan ke basis data',
                'id_aset' => $asset->id,
                'kode_aset' => $asset->code,
            ]);

            return response()->json([
                'message' => 'Aset berhasil diperbarui',
                'data' => $asset,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal menghubungi basis data saat memperbarui aset', [
                'siapa' => $request->user()?->email ?? 'pengguna_terautentikasi',
                'sedang_mengerjakan' => 'memperbarui_data_aset',
                'sebab' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal menghubungi basis data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $assetId = $asset->id;
        $assetCode = $asset->code;
        $asset->delete();

        Log::info('Data aset berhasil dihapus', [
            'siapa' => request()->user()?->email ?? 'pengguna_terautentikasi',
            'sedang_mengerjakan' => 'menghapus_data_aset',
            'sebab' => 'Penghapusan data aset dari basis data selesai',
            'id_aset' => $assetId,
            'kode_aset' => $assetCode,
        ]);

        return response()->json([
            'message' => 'Aset berhasil dihapus',
        ], 200);
    }
}
