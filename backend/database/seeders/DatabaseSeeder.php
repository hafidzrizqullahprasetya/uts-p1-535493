<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User Admin untuk pengujian autentikasi
        User::updateOrCreate(
            ['email' => 'admin@ugm.ac.id'],
            [
                'name' => 'Administrator Lab TRPL',
                'password' => Hash::make('password123'),
            ]
        );

        // Data awal inventaris aset laboratorium
        $assets = [
            [
                'name' => 'Workstation Lenovo ThinkStation P360',
                'code' => 'AST-LAB-001',
                'category' => 'Komputer & Server',
                'status' => 'tersedia',
                'price' => 24500000,
                'location' => 'HU.108 Laboratorium RPL 1',
            ],
            [
                'name' => 'Laptop Dell Latitude 5430',
                'code' => 'AST-LAB-002',
                'category' => 'Komputer Portable',
                'status' => 'dipinjam',
                'price' => 17800000,
                'location' => 'HU.104 Laboratorium RPL 4',
            ],
            [
                'name' => 'Manageable Switch Cisco Catalyst 2960',
                'code' => 'AST-LAB-003',
                'category' => 'Perangkat Jaringan',
                'status' => 'tersedia',
                'price' => 8500000,
                'location' => 'Ruang Server TEDI',
            ],
            [
                'name' => 'Digital Storage Oscilloscope Rigol DS1054Z',
                'code' => 'AST-LAB-004',
                'category' => 'Instrumen Elektronika',
                'status' => 'perawatan',
                'price' => 7200000,
                'location' => 'Laboratorium IoT & Instrumentasi',
            ],
            [
                'name' => 'Interactive Display Smart Board 75 Inch',
                'code' => 'AST-LAB-005',
                'category' => 'Multimedia & Presentasi',
                'status' => 'tersedia',
                'price' => 38000000,
                'location' => 'HU.108 Laboratorium RPL 1',
            ],
        ];

        foreach ($assets as $asset) {
            Asset::updateOrCreate(['code' => $asset['code']], $asset);
        }
    }
}
