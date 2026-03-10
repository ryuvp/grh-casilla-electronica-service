# Implementacion Phase 1 - Estado Actualizado

**Fecha de actualizacion:** 2026-03-09  
**Servicio:** `grh-casilla-electronica-service`  
**Fase:** Backend Phase 1 (cerrada y alineada a convenciones vigentes)

## 1. Objetivo de la fase

Completar la base backend para casillas y mensajeria con:

- autenticacion remota por token,
- paginacion uniforme,
- soporte de adjuntos y referencias SGD,
- validaciones de negocio del dominio,
- documentacion tecnica actualizada.

## 2. Decision de arquitectura vigente

Convencion oficial acordada por el equipo:

- Los permisos y roles funcionales se gestionan en frontend.
- El backend no aplica ACL por permisos del auth-service.
- El backend valida:
  - token (middleware `remoteauth`),
  - integridad de datos,
  - reglas de negocio.

Esta decision reemplaza el enfoque previo de validacion por actor/permiso en controladores backend.

## 3. Implementacion realizada

### 3.1 Autenticacion y usuario autenticado

- Se normalizo el acceso a `auth_user` inyectado por `RemoteAuth`.
- En `MensajeController`, `getAuthUser()` ahora es seguro ante valores nulos/no array.

Archivo:
- `backend/app/Http/Controllers/MensajeController.php`

### 3.2 Paginacion estandar

Se aplico paginacion obligatoria (`per_page` default 10, max 100) en:

- `CasillaController@index`
- `MensajeController@bandejaEntrada`
- `MensajeController@bandejaEnviados`

Archivos:
- `backend/app/Http/Controllers/CasillaController.php`
- `backend/app/Http/Controllers/MensajeController.php`

### 3.3 Soporte de `sgd_referencias`

Se agrego soporte end-to-end para referencias a documentos SGD:

- migracion con columna JSON nullable,
- `fillable`, `casts` y validaciones en modelo,
- serializacion en resource.

Archivos:
- `backend/database/migrations/2025_06_19_205000_add_sgd_referencias_to_mensajes_table.php`
- `backend/app/Models/Mensaje.php`
- `backend/app/Http/Resources/MensajeResource.php`

### 3.4 Limpieza de ACL backend (ajuste posterior)

Para alinear la convencion frontend-only permisos se elimino:

- validacion por `isAdministradorCasillas()` en `CasillaController@store` y `update`,
- trait de ACL `ActorValidation`,
- codigo muerto asociado a validacion por roles/permisos.

Cambios:
- `backend/app/Http/Controllers/CasillaController.php`
- `backend/app/Http/Controllers/MensajeController.php`
- eliminado `backend/app/Http/Traits/ActorValidation.php`

### 3.5 Reglas de negocio mantenidas en backend

Se mantienen activas reglas de dominio, por ejemplo:

- no enviar mensaje a destino sin casilla activa,
- validaciones estructurales del payload,
- transacciones para escrituras criticas.

Archivo principal:
- `backend/app/Http/Controllers/MensajeController.php`

## 4. Validaciones ejecutadas

### 4.1 Suite automatica

Comando:

```bash
docker compose exec app php artisan test
```

Resultado:

```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
Tests: 2 passed (2 assertions)
```

### 4.2 Endpoints validados manualmente

- `GET /api/casillas?per_page=5` -> `200 OK` con paginacion.
- `POST /api/casillas` -> `201 Created` (sin bloqueo ACL backend).
- `GET /api/mensajes/entrada?per_page=5` -> `200 OK`.
- `GET /api/mensajes/enviados?per_page=5` -> `200 OK`.
- `POST /api/mensajes` -> `400 Bad Request` cuando destino no tiene casilla activa (regla de negocio correcta).

## 5. Estado de artefactos

### Archivos creados en la fase

- `backend/database/migrations/2025_06_19_205000_add_sgd_referencias_to_mensajes_table.php`

### Archivos actualizados

- `backend/app/Http/Controllers/CasillaController.php`
- `backend/app/Http/Controllers/MensajeController.php`
- `backend/app/Models/Mensaje.php`
- `backend/app/Http/Resources/MensajeResource.php`
- `docs/01_arquitectura.md`
- `docs/02_contratos.md`
- `docs/03_reglas_de_codigo.md`
- `context.md`
- `context-general.md`
- `context-backend.md`
- `context-frontend.md`
- `context-new-module.md`

### Archivos eliminados por obsolescencia

- `backend/app/Http/Traits/ActorValidation.php`

## 6. Criterio de cierre de Phase 1

Phase 1 se considera cerrada porque:

- backend funcional para casillas y mensajes,
- paginacion y contratos de respuesta estandarizados,
- soporte de referencias SGD implementado,
- convencion de autorizacion frontend-only aplicada en codigo y documentacion,
- pruebas base ejecutadas sin fallos.

## 7. Pendientes de la siguiente fase

- construir test suite funcional real (no solo `ExampleTest`),
- pruebas de integracion con auth/file/sgd,
- refinamiento frontend para flujo completo de permisos y acciones,
- cobertura de casos de negocio adicionales (apertura de casilla, escenarios de lectura/edicion avanzada).

## 8. Referencias

- `RESULTADOS_PRUEBAS_PHASE1.md`
- `docs/01_arquitectura.md`
- `docs/02_contratos.md`
- `docs/03_reglas_de_codigo.md`
- `context-backend.md`

**Estado final:** `COMPLETADO` y `ALINEADO A CONVENCION VIGENTE`.
