<?php

namespace Tests\Feature\Signature;

use App\Http\Middleware\RemoteAuth;
use App\Models\Casilla;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Remote auth es integración externa; aquí se inyecta auth_user manualmente.
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

    private function authReader(int $designacionId): array
    {
        return [
            'designacion_id' => $designacionId,
            'roles' => [
                ['name' => 'usuario'],
            ],
        ];
    }

    public function test_generar_certificado_pdf_exitoso(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-PDF-001');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-PDF-001');

        $mensaje = Mensaje::create([
            'asunto' => 'Mensaje para PDF',
            'prioridad' => 1,
            'contenido' => 'Contenido del mensaje',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
            'leido' => true,
            'read_at' => now(),
        ]);

        $response = $this->call('GET', '/api/mensajes/' . $mensaje->id . '/certificado-pdf', [
            'auth_user' => $this->authReader(1),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_generar_constancia_envio_pdf_exitoso(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-PDF-002');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-PDF-002');

        $mensaje = Mensaje::create([
            'asunto' => 'Mensaje para PDF Envio',
            'prioridad' => 1,
            'contenido' => 'Contenido del mensaje Envio',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
        ]);

        $response = $this->call('GET', '/api/mensajes/' . $mensaje->id . '/constancia-envio-pdf', [
            'auth_user' => $this->authReader(1),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_generar_constancia_lectura_pdf_exitoso(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $casillaOrigen = $this->createActiveCasilla(1, 'CAS-ORIG-PDF-003');
        $casillaDestino = $this->createActiveCasilla(2, 'CAS-DEST-PDF-003');

        $mensaje = Mensaje::create([
            'asunto' => 'Mensaje para PDF Lectura',
            'prioridad' => 1,
            'contenido' => 'Contenido del mensaje Lectura',
            'casilla_origen_id' => $casillaOrigen->id,
            'casilla_destino_id' => $casillaDestino->id,
            'leido' => true,
            'read_at' => now(),
        ]);

        $response = $this->call('GET', '/api/mensajes/' . $mensaje->id . '/constancia-lectura-pdf', [
            'auth_user' => $this->authReader(1),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
