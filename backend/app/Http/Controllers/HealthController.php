<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [];
        $bagianBermasalah = [];
        $statusKeseluruhan = 'healthy';

        // 1. Pemeriksaan Basis Data
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $checks['database'] = [
                'status' => 'healthy',
                'pesan' => 'Koneksi basis data aktif dan responsif',
            ];
        } catch (\Throwable $e) {
            $statusKeseluruhan = 'unhealthy';
            $bagianBermasalah[] = 'database';
            $checks['database'] = [
                'status' => 'unhealthy',
                'pesan' => 'Gagal menghubungi basis data',
                'detail' => $e->getMessage(),
            ];

            Log::error('Healthcheck mendeteksi kegagalan koneksi basis data', [
                'siapa' => 'sistem_pemantau',
                'sedang_mengerjakan' => 'pemeriksaan_kesehatan_database',
                'sebab' => $e->getMessage(),
            ]);
        }

        // 2. Pemeriksaan Cache
        try {
            $probeKey = 'health_probe_'.bin2hex(random_bytes(4));
            Cache::put($probeKey, 'probe_value', 10);
            $probeResult = Cache::get($probeKey);
            Cache::forget($probeKey);

            if ($probeResult === 'probe_value') {
                $checks['cache'] = [
                    'status' => 'healthy',
                    'pesan' => 'Operasi baca dan tulis cache berfungsi normal',
                ];
            } else {
                throw new \RuntimeException('Nilai probe cache tidak sesuai');
            }
        } catch (\Throwable $e) {
            $statusKeseluruhan = 'unhealthy';
            $bagianBermasalah[] = 'cache';
            $checks['cache'] = [
                'status' => 'unhealthy',
                'pesan' => 'Gagal mengoperasikan sistem cache',
                'detail' => $e->getMessage(),
            ];

            Log::error('Healthcheck mendeteksi kegagalan sistem cache', [
                'siapa' => 'sistem_pemantau',
                'sedang_mengerjakan' => 'pemeriksaan_kesehatan_cache',
                'sebab' => $e->getMessage(),
            ]);
        }

        $payload = [
            'status' => $statusKeseluruhan,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        if ($statusKeseluruhan === 'unhealthy') {
            $payload['bagian_bermasalah'] = $bagianBermasalah;

            return response()->json($payload, 503);
        }

        return response()->json($payload, 200);
    }
}
