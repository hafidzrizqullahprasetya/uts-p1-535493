<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_unauthenticated_request_to_assets_returns_401(): void
    {
        $response = $this->getJson('/api/assets');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_all_assets(): void
    {
        Asset::create([
            'name' => 'Laptop HP ProBook',
            'code' => 'AST-TEST-001',
            'category' => 'Laptop',
            'status' => 'tersedia',
            'price' => 12000000,
            'location' => 'Lab 1',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'category', 'status', 'price', 'location', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_authenticated_user_can_create_asset_with_201_status(): void
    {
        $payload = [
            'name' => 'Router MikroTik CCR1009',
            'code' => 'AST-NET-001',
            'category' => 'Jaringan',
            'status' => 'tersedia',
            'price' => 6500000,
            'location' => 'Rak Server Lantai 2',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/assets', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Aset berhasil dibuat',
                'data' => [
                    'name' => 'Router MikroTik CCR1009',
                    'code' => 'AST-NET-001',
                    'category' => 'Jaringan',
                    'status' => 'tersedia',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'code' => 'AST-NET-001',
        ]);
    }

    public function test_create_asset_fails_validation_returns_422(): void
    {
        $payload = [
            'name' => '',
            'code' => '',
            'category' => '',
            'status' => 'status-ngawur',
            'price' => -100,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/assets', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'category', 'status', 'price']);
    }

    public function test_authenticated_user_can_view_single_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Server Dell PowerEdge R740',
            'code' => 'AST-SRV-001',
            'category' => 'Server',
            'status' => 'tersedia',
            'price' => 85000000,
            'location' => 'Data Center SV UGM',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/assets/'.$asset->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Detail aset berhasil diambil',
                'data' => [
                    'id' => $asset->id,
                    'code' => 'AST-SRV-001',
                ],
            ]);
    }

    public function test_authenticated_user_can_update_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Proyektor Epson EB-X500',
            'code' => 'AST-PRJ-001',
            'category' => 'Multimedia',
            'status' => 'tersedia',
            'price' => 5800000,
            'location' => 'R. Kuliah CU 208',
        ]);

        $updatePayload = [
            'name' => 'Proyektor Epson EB-X500 (Updated)',
            'code' => 'AST-PRJ-001',
            'category' => 'Multimedia',
            'status' => 'dipinjam',
            'price' => 5800000,
            'location' => 'HU.104 Laboratorium RPL 4',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/assets/'.$asset->id, $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Aset berhasil diperbarui',
                'data' => [
                    'name' => 'Proyektor Epson EB-X500 (Updated)',
                    'status' => 'dipinjam',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'dipinjam',
        ]);
    }

    public function test_authenticated_user_can_delete_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Mouse USB Rusak',
            'code' => 'AST-ACC-999',
            'category' => 'Aksesoris',
            'status' => 'rusak',
            'price' => 50000,
            'location' => 'Gudang',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/assets/'.$asset->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Aset berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('assets', [
            'id' => $asset->id,
        ]);
    }
}
