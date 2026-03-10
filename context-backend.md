# CONTEXTO BACKEND - GRH Casilla Electronica Service

## 1. Estructura relevante

- `backend/routes/api.php`
- `backend/app/Http/Controllers/`
- `backend/app/Models/`
- `backend/app/Http/Resources/`
- `backend/app/Http/Middleware/RemoteAuth.php`
- `backend/app/Filters/Filterable.php`

## 2. Rutas

Protegidas por `remoteauth`:
- `Route::apiResource('/casillas', CasillaController::class)`
- `GET /mensajes/entrada`
- `GET /mensajes/enviados`
- `POST /mensajes`
- `GET /mensajes/{mensaje}`
- `POST /mensajes/{mensaje}/leido`

## 3. Modelos

### Casilla
Campos principales: `numero`, `titular_tipo`, `titular_id`, `activo`, `fecha_inicio`, `fecha_fin`.

### Mensaje
Campos principales: `asunto`, `prioridad`, `contenido`, `leido`, `usuario_origen_id`, `usuario_destino_id`, `fecha_envio`, `fecha_leido`.
Relacion: `adjuntos()`.

Capacidad funcional del mensaje:
- adjuntos digitales via `archivo_ids` (File Service),
- referencias a documentos/archivos del SGD via `sgd_referencias` (contrato de integracion).

### Adjunto
Campos: `mensaje_id`, `archivo_id`.

## 4. Patrnones en uso

- Trait `Filterable` para filtros desde query params.
- `JsonResource` para serializacion (`CasillaResource`, `MensajeResource`).
- Transacciones en escrituras criticas.

## 5. Convenciones objetivo

- `index()` siempre paginado (default `per_page=10`, max `100`).
- No usar `get()` en endpoints de listado de alto volumen.
- Usuario autenticado disponible de forma consistente para todos los controladores.
- Respuestas de error estandarizadas (`status`, `message`, `errors`).
- Soportar en contrato de creacion de mensajes tanto adjuntos digitales como referencias a SGD.
- No aplicar ACL por permisos/roles en backend; esa autorizacion funcional pertenece al frontend.
- Backend valida token (`remoteauth`) y reglas de negocio del dato.

## 6. Deuda tecnica detectada (referencial)

- Manejo de usuario autenticado no uniforme entre middleware y controladores.
- Parametros legados en listados (`limit`, `hash`) pendientes de estandarizar.
- Ajustes de filtros y validaciones por normalizar.
