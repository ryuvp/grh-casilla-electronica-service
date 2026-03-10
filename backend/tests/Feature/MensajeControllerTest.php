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

        // Remote auth is integration behavior; for feature tests we inject auth_user manually.
        $this->withoutMiddleware(RemoteAuth::class);
    }

    public function test_bandeja_entrada_returns_only_messages_for_authenticated_destination(): void
    {
        Mensaje::create([
            'asunto' => 'Entrada U1',
            'prioridad' => 3,
            'contenido' => 'Mensaje para usuario 1',
            'usuario_origen_id' => 99,
            'usuario_destino_id' => 1,
            'fecha_envio' => '2026-03-09',
        ]);

        Mensaje::create([
            'asunto' => 'Entrada U2',
            'prioridad' => 3,
            'contenido' => 'Mensaje para usuario 2',
            'usuario_origen_id' => 99,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $response = $this->getJson('/api/mensajes/entrada?per_page=10&auth_user[id]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 10);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Entrada U1', $response->json('data.0.asunto'));
    }

    public function test_bandeja_enviados_returns_only_messages_for_authenticated_origin(): void
    {
        Mensaje::create([
            'asunto' => 'Enviado por U1',
            'prioridad' => 2,
            'contenido' => 'Mensaje enviado por usuario 1',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 50,
            'fecha_envio' => '2026-03-09',
        ]);

        Mensaje::create([
            'asunto' => 'Enviado por U2',
            'prioridad' => 2,
            'contenido' => 'Mensaje enviado por usuario 2',
            'usuario_origen_id' => 2,
            'usuario_destino_id' => 50,
            'fecha_envio' => '2026-03-09',
        ]);

        $response = $this->getJson('/api/mensajes/enviados?per_page=10&auth_user[id]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 10);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Enviado por U1', $response->json('data.0.asunto'));
    }

    public function test_store_creates_message_with_adjuntos_and_sgd_referencias_when_destination_has_active_casilla(): void
    {
        Casilla::create([
            'numero' => 'CAS-DEST-001',
            'titular_tipo' => 1,
            'titular_id' => 2,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
        ]);

        $payload = [
            'asunto' => 'Mensaje con refs',
            'prioridad' => 3,
            'contenido' => 'Contenido del mensaje',
            'usuario_destino_id' => 2,
            'archivo_ids' => [101, 202],
            'sgd_referencias' => [
                ['documento_id' => 123, 'tipo' => 'HOJA_DE_RUTA'],
                ['documento_id' => 124, 'tipo' => 'INFORME'],
            ],
            'normatividad_referencias' => [
                ['normatividad_id' => 501],
            ],
            'auth_user' => ['id' => 1],
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('mensajes', [
            'asunto' => 'Mensaje con refs',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
        ]);

        $mensaje = Mensaje::firstOrFail();
        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 101,
            'tipo' => 'archivo',
        ]);

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 202,
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

        $response->assertJsonPath('data.archivo_ids.0', 101);
        $response->assertJsonPath('data.sgd_referencias.0.documento_id', 123);
        $response->assertJsonPath('data.normatividad_referencias.0.normatividad_id', 501);
    }

    public function test_store_returns_bad_request_when_destination_has_no_active_casilla(): void
    {
        $payload = [
            'asunto' => 'Mensaje sin casilla destino',
            'prioridad' => 3,
            'contenido' => 'Contenido',
            'usuario_destino_id' => 2,
            'auth_user' => ['id' => 1],
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertBadRequest();
        $response->assertJsonPath('message', 'El usuario destinatario no tiene una casilla electrónica activa');
    }

    public function test_marcar_leido_allows_only_destination_user(): void
    {
        $mensaje = Mensaje::create([
            'asunto' => 'Marcar leido',
            'prioridad' => 1,
            'contenido' => 'Pendiente de lectura',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $forbiddenResponse = $this->postJson('/api/mensajes/' . $mensaje->id . '/leido', [
            'auth_user' => ['id' => 1],
        ]);

        $forbiddenResponse->assertForbidden();

        $allowedResponse = $this->postJson('/api/mensajes/' . $mensaje->id . '/leido', [
            'auth_user' => ['id' => 2],
        ]);

        $allowedResponse->assertOk();

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'leido' => true,
        ]);
    }

    public function test_destroy_soft_deletes_message_only_for_origin_user(): void
    {
        $mensaje = Mensaje::create([
            'asunto' => 'Eliminar mensaje',
            'prioridad' => 1,
            'contenido' => 'Mensaje a eliminar logicamente',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $forbiddenResponse = $this->deleteJson('/api/mensajes/' . $mensaje->id, [
            'auth_user' => ['id' => 2],
        ]);

        $forbiddenResponse->assertForbidden();

        $allowedResponse = $this->deleteJson('/api/mensajes/' . $mensaje->id, [
            'auth_user' => ['id' => 1],
        ]);

        $allowedResponse->assertOk();
        $allowedResponse->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('mensajes', [
            'id' => $mensaje->id,
        ]);
    }

    public function test_show_allows_origin_or_destination_user_and_forbids_others(): void
    {
        $mensaje = Mensaje::create([
            'asunto' => 'Ver mensaje',
            'prioridad' => 2,
            'contenido' => 'Contenido visible para origen y destino',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $originResponse = $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => ['id' => 1],
        ]);
        $originResponse->assertOk();
        $originResponse->assertJsonPath('data.id', $mensaje->id);

        $destinationResponse = $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => ['id' => 2],
        ]);
        $destinationResponse->assertOk();
        $destinationResponse->assertJsonPath('data.id', $mensaje->id);

        $forbiddenResponse = $this->call('GET', '/api/mensajes/' . $mensaje->id, [
            'auth_user' => ['id' => 999],
        ]);
        $forbiddenResponse->assertForbidden();
    }

    public function test_update_allows_only_origin_user_and_updates_message_data(): void
    {
        $mensaje = Mensaje::create([
            'asunto' => 'Asunto original',
            'prioridad' => 2,
            'contenido' => 'Contenido original',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $forbiddenPayload = [
            'asunto' => 'Intento no autorizado',
            'prioridad' => 3,
            'contenido' => 'No debe actualizarse',
            'usuario_destino_id' => 2,
            'auth_user' => ['id' => 2],
        ];

        $forbiddenResponse = $this->putJson('/api/mensajes/' . $mensaje->id, $forbiddenPayload);
        $forbiddenResponse->assertForbidden();

        $allowedPayload = [
            'asunto' => 'Asunto actualizado',
            'prioridad' => 4,
            'contenido' => 'Contenido actualizado',
            'usuario_destino_id' => 2,
            'sgd_referencias' => [
                ['documento_id' => 777, 'tipo' => 'INFORME'],
            ],
            'normatividad_referencias' => [
                ['normatividad_id' => 888],
            ],
            'auth_user' => ['id' => 1],
        ];

        $allowedResponse = $this->putJson('/api/mensajes/' . $mensaje->id, $allowedPayload);
        $allowedResponse->assertOk();
        $allowedResponse->assertJsonPath('data.asunto', 'Asunto actualizado');

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'asunto' => 'Asunto actualizado',
            'prioridad' => 4,
            'contenido' => 'Contenido actualizado',
        ]);

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 777,
            'tipo' => 'documento_sgd',
        ]);

        $this->assertDatabaseHas('adjuntos', [
            'mensaje_id' => $mensaje->id,
            'referencia_id' => 888,
            'tipo' => 'normatividad',
        ]);

        $allowedResponse->assertJsonPath('data.sgd_referencias.0.documento_id', 777);
        $allowedResponse->assertJsonPath('data.normatividad_referencias.0.normatividad_id', 888);
    }

    public function test_update_returns_bad_request_when_payload_is_invalid(): void
    {
        $mensaje = Mensaje::create([
            'asunto' => 'Asunto valido',
            'prioridad' => 2,
            'contenido' => 'Contenido valido',
            'usuario_origen_id' => 1,
            'usuario_destino_id' => 2,
            'fecha_envio' => '2026-03-09',
        ]);

        $invalidPayload = [
            // Falta asunto (requerido)
            'prioridad' => 3,
            'contenido' => 'Contenido actualizado',
            'usuario_destino_id' => 2,
            'auth_user' => ['id' => 1],
        ];

        $response = $this->putJson('/api/mensajes/' . $mensaje->id, $invalidPayload);

        $response->assertBadRequest();
        $response->assertJsonPath('status', 'error');
        $this->assertArrayHasKey('asunto', $response->json('errors'));
    }

    public function test_store_returns_bad_request_when_sgd_referencias_structure_is_invalid(): void
    {
        Casilla::create([
            'numero' => 'CAS-DEST-EDGE-001',
            'titular_tipo' => 1,
            'titular_id' => 2,
            'activo' => true,
            'fecha_inicio' => '2026-03-09',
        ]);

        $payload = [
            'asunto' => 'Mensaje con estructura invalida',
            'prioridad' => 3,
            'contenido' => 'Contenido',
            'usuario_destino_id' => 2,
            // Falta campo obligatorio tipo en el item
            'sgd_referencias' => [
                ['documento_id' => 123],
            ],
            'auth_user' => ['id' => 1],
        ];

        $response = $this->postJson('/api/mensajes', $payload);

        $response->assertBadRequest();
        $response->assertJsonPath('status', 'error');
        $this->assertArrayHasKey('sgd_referencias.0.tipo', $response->json('errors'));
    }
}
