# Resultados de Pruebas - Backend Phase 1

**Fecha de ejecucion:** 2026-03-09  
**Servicio:** `grh-casilla-electronica-service`  
**Ambiente:** Docker local (`app`, `web`, `db`, `frontend`)  

## 1. Resumen ejecutivo

- Se implemento y valido `soft delete` para `casillas` y `mensajes`.
- Se agregaron pruebas feature para endpoints criticos de casillas y mensajes.
- Se validaron reglas de negocio de mensajeria (casilla activa destino).
- Se confirmo la convencion vigente: permisos/roles funcionales en frontend.
- El backend mantiene validacion de token + reglas de negocio del dato.

## 2. Cambios validados en esta ejecucion

1. `DELETE /api/casillas/{id}`
- Ahora realiza eliminacion logica (soft delete).

2. `DELETE /api/mensajes/{id}`
- Se habilito ruta y elimina logicamente solo si el usuario es origen.

3. Modelos con `SoftDeletes`
- `Casilla`
- `Mensaje`

4. Migracion ejecutada
- `2026_03_09_235500_add_soft_deletes_to_casillas_table`

5. Refactor de referencias externas en mensajes
- `adjuntos` ahora soporta referencias tipadas con `tipo` y `referencia_id`.
- Se simplifico el esquema removiendo `archivo_id` (no duplicar identificadores).
- Se mantienen adjuntos de archivos (`tipo=archivo`) y se incorporan:
	- `tipo=documento_sgd`
	- `tipo=normatividad`

## 3. Pruebas automaticas ejecutadas

### 3.1 Feature tests focalizados

Comando:

```bash
docker compose exec app php artisan test --filter='CasillaControllerTest|MensajeControllerTest'
```

Resultado:

```text
PASS  Tests\Feature\CasillaControllerTest
- index returns paginated data with requested per page
- store creates a casilla
- update modifies an existing casilla
- destroy soft deletes a casilla

PASS  Tests\Feature\MensajeControllerTest
- bandeja entrada returns only messages for authenticated destination
- bandeja enviados returns only messages for authenticated origin
- show allows origin or destination user and forbids others
- store creates message with adjuntos and sgd referencias when destination has active casilla
- update allows only origin user and updates message data
- update returns bad request when payload is invalid
- store returns bad request when destination has no active casilla
- store returns bad request when sgd referencias structure is invalid
- marcar leido allows only destination user
- destroy soft deletes message only for origin user

Tests: 14 passed (55 assertions)
```

Estado: `PASS`

### 3.2 Suite completa

Comando:

```bash
docker compose exec app php artisan test
```

Resultado:

```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\CasillaControllerTest
PASS  Tests\Feature\MensajeControllerTest

Tests: 16 passed (57 assertions)
```

Estado: `PASS`

## 4. Detalle por modulo

### 4.1 Casillas

Cobertura validada:

- Listado paginado con `per_page` solicitado.
- Creacion de casilla.
- Actualizacion de casilla.
- Eliminacion logica con `deleted_at` (soft delete).

Resultado: `4/4 PASS`

### 4.2 Mensajes

Cobertura validada:

- Bandeja de entrada filtrada por `usuario_destino_id` autenticado.
- Bandeja de enviados filtrada por `usuario_origen_id` autenticado.
- `GET /mensajes/{id}`: acceso permitido solo para usuario origen o destino.
- Creacion de mensaje con `archivo_ids` y `sgd_referencias`.
- `PUT /mensajes/{id}`: actualizacion permitida solo para usuario origen.
- `PUT /mensajes/{id}`: error `400` para payload invalido.
- Error `400` cuando destino no tiene casilla activa.
- Error `400` cuando `sgd_referencias` tiene estructura invalida.
- Marcado de lectura solo para destinatario.
- Eliminacion logica solo para usuario origen.
- Persistencia de referencias externas en `adjuntos` con tipos: `archivo`, `documento_sgd`, `normatividad`.

Resultado: `10/10 PASS`

## 5. Verificaciones de negocio

1. Regla de casilla activa en destino
- Validada: si no existe casilla activa, `POST /mensajes` responde `400`.

2. Restriccion de propiedad de mensaje
- Validada: `DELETE /mensajes/{id}` solo permitido para usuario origen.

3. Soft delete
- Validado con `assertSoftDeleted` en `casillas` y `mensajes`.

4. Validacion estructural de contrato
- Validado: `sgd_referencias` exige `documento_id` y `tipo` por item.
- Validado: update responde `400` cuando falta un campo requerido (`asunto`).

5. Normalizacion de almacenamiento de referencias
- Validado: las referencias SGD y normatividad se guardan como filas en `adjuntos`.
- Validado: `MensajeResource` devuelve referencias derivadas de `adjuntos` tipados.
- Validado: una sola columna de identificador externo (`referencia_id`) para todos los tipos.

## 6. Evidencia de migracion

Comando ejecutado:

```bash
docker compose exec app php artisan migrate
```

Salida relevante:

```text
INFO  Running migrations.
2026_03_09_235500_add_soft_deletes_to_casillas_table ... DONE
```

## 7. Estado de cumplimiento

- Convencion frontend-only para permisos/roles: `CUMPLE`.
- Paginacion obligatoria en listados: `CUMPLE`.
- Validacion de token via `remoteauth`: `CUMPLE`.
- Reglas de negocio backend: `CUMPLE`.
- Eliminacion logica (soft delete): `CUMPLE`.
- Suite de pruebas ejecutada: `PASS`.

## 8. Pendientes recomendados

- Agregar tests para `GET /mensajes/{id}` autorizado/no autorizado.
- Agregar tests para `PUT /mensajes/{id}` y consistencia de `sgd_referencias` en update.
- Agregar tests de integracion reales contra Auth Service (token real en pipeline de QA).

## 9. Conclusiones

La fase backend actual queda validada con cobertura feature para los endpoints principales y con soporte de `soft delete` implementado y probado.

Adicionalmente, el almacenamiento de referencias externas se normalizo a nivel de tabla `adjuntos`, permitiendo multiples documentos SGD por mensaje y extensibilidad para otras fuentes (normatividad).

Estado general: `APROBADO` para continuar con la siguiente iteracion.
