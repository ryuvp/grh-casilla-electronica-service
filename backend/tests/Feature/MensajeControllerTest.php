<?php

namespace Tests\Feature;

use App\Http\Middleware\RemoteAuth;
use App\Models\Casilla;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MensajeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Remote auth es integracion externa; aqui se inyecta auth_user manualmente.
        $this->withoutMiddleware(RemoteAuth::class);
    }

    private function createActiveCasilla(int $designacionId, string $numero): Casilla
    {
        return Casilla::create([
            'numero' => $numero,
            'designacion_id' => $designacionId,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
        ]);
    }

    private function authWriter(int $designacionId): array
    {
        return [
            'designacion_id' => $designacionId,
            'roles' => [
                ['name' => 'admin'],
            ],
        ];
    }

    private function authReader(int $designacionId): array
    {
        return [
            'designacion_id' => $designacionId,
            'roles' => [
                ['name' => 'usuario'],
            ],
        ];
    }

    public function test_bandeja_entrada_returns_only_messages_for_authenticated_destination_casilla(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-ENT-001');
        $casillaOtro = $this->createActiveCasilla(2, 'CAS-OTRO-ENT-001');
        $casillaOrigen = $this->createActiveCasilla(99, 'CAS-ORIG-ENT-001');

        Mensaje::create([
            'asunto' => 'Entrada C1',
            'prioridad' => 3,
            'contenido' => 'Mensaje para casilla auth',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
        ]);

        Mensaje::create([
            'asunto' => 'Entrada C2',
            'prioridad' => 3,
            'contenido' => 'Mensaje para otra casilla',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaOtro->id,
        ]);

        $response = $this->getJson('/api/mensajes/entrada?per_page=10&auth_user[designacion_id]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 10);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Entrada C1', $response->json('data.0.asunto'));
    }

    public function test_bandeja_entrada_excludes_archived_messages(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-ENT-ARC-001');
        $casillaOrigen = $this->createActiveCasilla(99, 'CAS-ORIG-ENT-ARC-001');

        Mensaje::create([
            'asunto' => 'Entrada visible',
            'prioridad' => 3,
            'contenido' => 'Visible en bandeja',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'archivado' => false,
        ]);

        Mensaje::create([
            'asunto' => 'Entrada archivada',
            'prioridad' => 3,
            'contenido' => 'Debe quedar fuera',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'archivado' => true,
        ]);

        $response = $this->getJson('/api/mensajes/entrada?per_page=10&auth_user[designacion_id]=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Entrada visible', $response->json('data.0.asunto'));
    }

    public function test_bandeja_enviados_returns_only_messages_for_authenticated_origin_casilla(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-ENV-001');
        $casillaDestino = $this->createActiveCasilla(50, 'CAS-DEST-ENV-001');
        $casillaOtroOrigen = $this->createActiveCasilla(2, 'CAS-OTRO-ENV-001');

        Mensaje::create([
            'asunto' => 'Enviado por C1',
            'prioridad' => 2,
            'contenido' => 'Mensaje enviado por casilla auth',
            'casilla_origen_id' => $casillaAuth->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        Mensaje::create([
            'asunto' => 'Enviado por C2',
            'prioridad' => 2,
            'contenido' => 'Mensaje enviado por otra casilla',
            'casilla_origen_id' => $casillaOtroOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $response = $this->getJson('/api/mensajes/enviados?per_page=10&auth_user[designacion_id]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 10);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Enviado por C1', $response->json('data.0.asunto'));
    }

    public function test_bandeja_destacados_returns_only_starred_non_archived_messages_for_destination_casilla(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-DST-001');
        $casillaOrigen = $this->createActiveCasilla(99, 'CAS-ORIG-DST-001');
        $casillaOtro = $this->createActiveCasilla(2, 'CAS-OTRO-DST-001');

        Mensaje::create([
            'asunto' => 'Destacado visible',
            'prioridad' => 2,
            'contenido' => 'Debe verse',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'destacado' => true,
            'archivado' => false,
        ]);

        Mensaje::create([
            'asunto' => 'Destacado archivado',
            'prioridad' => 2,
            'contenido' => 'No debe verse aqui',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'destacado' => true,
            'archivado' => true,
        ]);

        Mensaje::create([
            'asunto' => 'Destacado ajeno',
            'prioridad' => 2,
            'contenido' => 'No debe verse aqui',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaOtro->id,
            'destacado' => true,
            'archivado' => false,
        ]);

        $response = $this->getJson('/api/mensajes/destacados?per_page=10&auth_user[designacion_id]=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Destacado visible', $response->json('data.0.asunto'));
    }

    public function test_bandeja_archivados_returns_only_archived_messages_for_destination_casilla(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-AR-001');
        $casillaOrigen = $this->createActiveCasilla(99, 'CAS-ORIG-AR-001');

        Mensaje::create([
            'asunto' => 'Archivado visible',
            'prioridad' => 2,
            'contenido' => 'Debe verse',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'archivado' => true,
        ]);

        Mensaje::create([
            'asunto' => 'No archivado',
            'prioridad' => 2,
            'contenido' => 'No debe verse',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaAuth->id,
            'archivado' => false,
        ]);

        $response = $this->getJson('/api/mensajes/archivados?per_page=10&auth_user[designacion_id]=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Archivado visible', $response->json('data.0.asunto'));
    }

    public function test_store_creates_message_with_references_when_origin_and_destination_have_active_casilla(): void
    {
        $casillaAuth = $this->createActiveCasilla(1, 'CAS-AUTH-ST-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-ST-001');

        $payload = [
            'asunto' => 'Mensaje con refs',
            'prioridad' => 3,
            'contenido' => 'Contenido del mensaje',
            'casilla_destino_id' => $casillaDestino->id,
            'archivo_ids' => [101, 202],
            'sgd_referencias' => [
                ['documento_id' => 123, 'tipo' => 'HOJA_DE_RUTA'],
                ['documento_id' => 124, 'tipo' => 'INFORME'],
            ],
            'normatividad_referencias' => [
                ['normatividad_id' => 501],
            ],
            'auth_user' => $this->authWriter(1),
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('mensajes', [
            'asunto' => 'Mensaje con refs',
            'casilla_origen_id' => $casillaAuth->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $mensaje = Mensaje::firstOrFail();

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 101,
            'tipo' => 'archivo',
        ]);

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 123,
            'tipo' => 'documento_sgd',
        ]);

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 501,
            'tipo' => 'normatividad',
        ]);

        $response->assertJsonPath('data.casilla_origen_id', $casillaAuth->id);
        $response->assertJsonPath('data.casilla_destino_id', $casillaDestino->id);
        $response->assertJsonPath('data.sgd_referencias.0.documento_id', 123);
    }

    public function test_store_returns_bad_request_when_destination_casilla_is_missing_or_inactive(): void
    {
        $this->createActiveCasilla(1, 'CAS-AUTH-ST-002');

        $payload = [
            'asunto' => 'Mensaje sin casilla destino',
            'prioridad' => 3,
            'contenido' => 'Contenido',
            'casilla_destino_id' => 999999,
            'auth_user' => $this->authWriter(1),
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertBadRequest();
        $response->assertJsonPath('message', 'La casilla destinataria no existe o no esta activa');
    }

    public function test_store_returns_forbidden_when_profile_cannot_write_notifications(): void
    {
        $this->createActiveCasilla(1, 'CAS-AUTH-ST-003');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-ST-003');

        $payload = [
            'asunto' => 'Intento de envio sin rol notificador',
            'prioridad' => 3,
            'contenido' => 'Contenido',
            'casilla_destino_id' => $casillaDestino->id,
            'auth_user' => $this->authReader(1),
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'No autorizado para enviar notificaciones');
    }

    public function test_marcar_leido_allows_only_destination_casilla(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-ML-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-ML-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Marcar leido',
            'prioridad' => 1,
            'contenido' => 'Pendiente de lectura',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $this->postJson('/api/mensajes/' . $mensaje->id . '/leido', [
            'auth_user' => $this->authReader(1),
        ])->assertForbidden();

        $this->postJson('/api/mensajes/' . $mensaje->id . '/leido', [
            'auth_user' => $this->authReader(2),
        ])->assertOk();

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'leido' => true,
        ]);
    }

    public function test_toggle_destacado_allows_only_destination_casilla(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-TD-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-TD-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Alternar destacado',
            'prioridad' => 1,
            'contenido' => 'Pendiente de destacado',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
            'destacado' => false,
        ]);

        $this->postJson('/api/mensajes/' . $mensaje->id . '/destacar', [
            'auth_user' => $this->authReader(1),
        ])->assertForbidden();

        $this->postJson('/api/mensajes/' . $mensaje->id . '/destacar', [
            'auth_user' => $this->authReader(2),
        ])->assertOk();

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'destacado' => true,
        ]);
    }

    public function test_toggle_archivado_allows_only_destination_casilla(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-TA-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-TA-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Alternar archivado',
            'prioridad' => 1,
            'contenido' => 'Pendiente de archivado',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
            'archivado' => false,
        ]);

        $this->postJson('/api/mensajes/' . $mensaje->id . '/archivar', [
            'auth_user' => $this->authReader(1),
        ])->assertForbidden();

        $this->postJson('/api/mensajes/' . $mensaje->id . '/archivar', [
            'auth_user' => $this->authReader(2),
        ])->assertOk();

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'archivado' => true,
        ]);
    }

    public function test_destroy_returns_method_not_allowed_because_operation_is_reserved(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-DEL-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-DEL-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Eliminar mensaje',
            'prioridad' => 1,
            'contenido' => 'Mensaje a eliminar logicamente',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $response = $this->deleteJson('/api/mensajes/' . $mensaje->id, [
            'auth_user' => $this->authWriter(1),
        ]);

        $response->assertStatus(405);
        $response->assertJsonPath('message', 'Operacion reservada: la eliminacion de mensajes no esta habilitada');

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
        ]);
    }

    public function test_show_allows_origin_or_destination_casilla_and_forbids_others(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-SH-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-SH-001');
        $this->createActiveCasilla(999, 'CAS-OTRA-SH-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Ver mensaje',
            'prioridad' => 2,
            'contenido' => 'Contenido visible para origen y destino',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => $this->authReader(1),
        ])->assertOk();

        $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => $this->authReader(2),
        ])->assertOk();

        $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => $this->authReader(999),
        ])->assertForbidden();
    }

    public function test_update_returns_method_not_allowed_because_operation_is_reserved(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-UPD-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-UPD-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Asunto original',
            'prioridad' => 2,
            'contenido' => 'Contenido original',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $payload = [
            'asunto' => 'Asunto actualizado',
            'prioridad' => 4,
            'contenido' => 'Contenido actualizado',
            'casilla_destino_id' => $casillaDestino->id,
            'sgd_referencias' => [
                ['documento_id' => 777, 'tipo' => 'INFORME'],
            ],
            'normatividad_referencias' => [
                ['normatividad_id' => 888],
            ],
            'auth_user' => $this->authWriter(1),
        ];

        $response = $this->putJson('/api/mensajes/' . $mensaje->id, $payload);
        $response->assertStatus(405);
        $response->assertJsonPath('message', 'Operacion reservada: la edicion de mensajes no esta habilitada');

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'asunto' => 'Asunto original',
        ]);
    }

    public function test_update_returns_method_not_allowed_even_when_payload_is_invalid(): void
    {
        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-UPD-ERR-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-UPD-ERR-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Asunto valido',
            'prioridad' => 2,
            'contenido' => 'Contenido valido',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $invalidPayload = [
            'prioridad' => 3,
            'contenido' => 'Contenido actualizado',
            'casilla_destino_id' => $casillaDestino->id,
            'auth_user' => $this->authWriter(1),
        ];

        $response = $this->putJson('/api/mensajes/' . $mensaje->id, $invalidPayload);

        $response->assertStatus(405);
        $response->assertJsonPath('message', 'Operacion reservada: la edicion de mensajes no esta habilitada');
    }

    public function test_store_returns_bad_request_when_sgd_referencias_structure_is_invalid(): void
    {
        $this->createActiveCasilla(1, 'CAS-ORIG-EDGE-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-EDGE-001');

        $payload = [
            'asunto' => 'Mensaje con estructura invalida',
            'prioridad' => 3,
            'contenido' => 'Contenido',
            'casilla_destino_id' => $casillaDestino->id,
            'sgd_referencias' => [
                ['documento_id' => 123],
            ],
            'auth_user' => $this->authWriter(1),
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertBadRequest();
        $response->assertJsonPath('status', 'error');
        $this->assertArrayHasKey('sgd_referencias.0.tipo', $response->json('errors'));
    }
}
