<?php

use App\Services\IpCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ip', function () {
    return view('ip');
});

Route::post('/ip/hitung', function (Request $request) {
    $request->validate([
        'nim' => ['required', 'string'],
        'mataKuliah' => ['required', 'array', 'min:1'],
        'mataKuliah.*.sks' => ['required', 'numeric'],
        'mataKuliah.*.nilai' => ['required', 'string'],
    ]);

    $nim = trim((string) $request->input('nim'));

    if (! IpCalculator::validasiNIM($nim)) {
        return view('ip', [
            'hasil' => 'Format NIM tidak valid. Contoh yang benar: 24/535493/SV/24243',
            'galat' => true,
        ]);
    }

    try {
        $mataKuliah = array_values($request->input('mataKuliah'));
        $ip = IpCalculator::hitungIP($mataKuliah);

        return view('ip', [
            'hasil' => "IP semester {$nim}: ".number_format($ip, 2),
            'galat' => false,
        ]);
    } catch (InvalidArgumentException $e) {
        return view('ip', [
            'hasil' => $e->getMessage(),
            'galat' => true,
        ]);
    }
});
