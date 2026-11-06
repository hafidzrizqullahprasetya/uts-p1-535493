<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_ok_when_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => [
                        'status' => 'healthy',
                    ],
                    'cache' => [
                        'status' => 'healthy',
                    ],
                ],
            ]);
    }

    public function test_health_check_returns_503_when_database_fails(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \RuntimeException('Koneksi basis data terputus'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => [
                        'status' => 'unhealthy',
                    ],
                ],
                'bagian_bermasalah' => ['database'],
            ]);
    }

    public function test_health_check_returns_503_when_cache_fails(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \RuntimeException('Layanan cache tidak dapat diakses'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => [
                    'cache' => [
                        'status' => 'unhealthy',
                    ],
                ],
                'bagian_bermasalah' => ['cache'],
            ]);
    }
}
