<?php

namespace Tests\Feature;

use App\Http\Middleware\RemoteAuth;
use App\Models\Casilla;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasillaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Remote auth is integration behavior; for feature tests we inject auth_user manually.
        $this->withoutMiddleware(RemoteAuth::class);
    }

    public function test_index_returns_paginated_data_with_requested_per_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Casilla::create([
                'numero' => 'CAS-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'designacion_id' => $i,
                'activo' => true,
                'fecha_inicio' => '2026-03-09',
            ]);
        }

        $response = $this->getJson('/api/casillas?per_page=5&auth_user[id]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 5);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_store_creates_a_casilla(): void
    {
        $payload = [
            'numero' => 'CAS-TEST-STORE-001',
            'designacion_id' => 101,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
            'auth_user' => ['id' => 1],
        ];

        $response = $this->postJson('/api/casillas', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('casillas', [
            'numero' => 'CAS-TEST-STORE-001',
            'designacion_id' => 101,
            'activo' => true,
        ]);
    }

    public function test_update_modifies_an_existing_casilla(): void
    {
        $casilla = Casilla::create([
            'numero' => 'CAS-TEST-UPD-001',
            'designacion_id' => 500,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
        ]);

        $payload = [
            'numero' => 'CAS-TEST-UPD-001-NEW',
            'designacion_id' => 500,
            'activo' => false,
            'fecha_inicio' => '2026-03-10',
            'auth_user' => ['id' => 1],
        ];

        $response = $this->putJson('/api/casillas/' . $casilla->id, $payload);

        $response->assertOk();

        $this->assertDatabaseHas('casillas', [
            'id' => $casilla->id,
            'numero' => 'CAS-TEST-UPD-001-NEW',
            'activo' => false,
        ]);
    }

    public function test_destroy_soft_deletes_a_casilla(): void
    {
        $casilla = Casilla::create([
            'numero' => 'CAS-TEST-DEL-001',
            'designacion_id' => 700,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
        ]);

        $response = $this->deleteJson('/api/casillas/' . $casilla->id, [
            'auth_user' => ['id' => 1],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('casillas', [
            'id' => $casilla->id,
        ]);
    }
}
