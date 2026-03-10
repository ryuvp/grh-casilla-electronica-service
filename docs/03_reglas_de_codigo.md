# 03 - Reglas de Codigo (estrictas)

Estado: Vigente  
Owner: Equipo Backend + Frontend (Tech Lead responsable)  
Ultima actualizacion: 2026-03-09  
Proxima revision: 2026-04-09

> Reglas obligatorias para todo el codigo del Casilla Service.
> Basadas en la metodologia aplicada en auth/sgd y ajustadas al dominio de casillas y mensajeria.
> Todo PR que viole estas reglas debe ser corregido antes de merge.

---

## 1. Reglas generales

- Idioma: comentarios/documentacion en espanol; nombres tecnicos en ingles.
- ASCII por defecto en codigo y comentarios; excepciones solo cuando sea necesario en UI/documentacion.
- Evitar logica de negocio en rutas.
- No exponer informacion sensible en logs o respuestas.
- Todo cambio funcional debe actualizar documentos canonicos (`docs/02`, `docs/04`, `context*.md`) en el mismo PR.

### G-01 - Comentarios obligatorios en todo el codigo

- Es obligatorio documentar con comentarios descriptivos todas las funciones/metodos/composables/acciones.
- Es obligatorio agregar comentarios descriptivos antes de cada bloque logico relevante dentro de una funcion.
- El comentario debe explicar objetivo, contexto y resultado esperado del bloque o funcion.
- No se aceptan comentarios vacios, ambiguos o triviales (ejemplo no valido: `// asigna valor`).
- En PR, codigo nuevo sin comentarios descriptivos debe considerarse incumplimiento de norma.

Ejemplo minimo esperado:

```js
// Valida que el usuario tenga casilla activa antes de permitir el envio.
function validarDestinoConCasillaActiva(destino) {
	// Si no existe destino o no esta activo, se corta el flujo con error de negocio.
	if (!destino || !destino.casilla_activa) {
		throw new Error('El usuario destino no tiene casilla activa')
	}

	// Retorna true para que el flujo de envio continue.
	return true
}
```

## 2. Reglas de Backend (Laravel)

### B-01 - Estructura y estilo

- Cumplir PSR-12 y formatear con `laravel/pint`.
- Controllers en `app/Http/Controllers`, Resources en `app/Http/Resources`, modelos en `app/Models`.
- Mantener separacion: controller orquesta, service/model ejecuta logica.

### B-02 - Paginacion obligatoria

- Todo `index()` de listados debe usar `paginate()`.
- `per_page` default obligatorio: `10`.
- `per_page` maximo: `100`.
- Queda prohibido devolver listados amplios con `get()`.

Ejemplo:

```php
$perPage = (int) ($request->per_page ?? 10);
$page = (int) ($request->page ?? 1);
$perPage = min($perPage, 100);

$result = $query->paginate($perPage, ['*'], 'page', $page);
return CasillaResource::collection($result);
```

### B-03 - Filtrado y ordenamiento

- Todo modelo listable debe usar `Filterable`.
- Ordenamiento debe soportar contrato backend-driven (`orders[]=campo.direccion`).
- No introducir parametros legacy nuevos.

### B-04 - Validacion y errores

- Validaciones base en `Model::$validables` y ajustes en `update` para reglas `unique`.
- Errores de validacion deben devolver `status=error` y `errors`.
- Errores de negocio deben devolver `status=error` y `message`.

### B-05 - Resources obligatorias

- Toda respuesta de entidad debe pasar por `JsonResource`.
- No retornar modelos Eloquent directos en endpoints publicos de API.

### B-06 - Seguridad y autenticacion (sin ACL por permisos en backend)

- Rutas privadas siempre dentro de middleware `remoteauth`.
- El backend valida token y reglas de negocio de datos (propiedad, estado, consistencia).
- La validacion de permisos/roles funcionales se realiza en frontend.
- No introducir ACL en backend basada en permisos del Auth Service, salvo excepcion aprobada por Tech Lead.

### B-07 - Reglas de negocio de mensajes

- El envio de mensajes solo se permite a usuarios con casilla activa.
- Un mensaje puede incluir:
	- `archivo_ids` (adjuntos digitales),
	- `sgd_referencias` (referencias a archivos/documentos del SGD).
- Operaciones multi-escritura (`mensaje + adjuntos + referencias`) deben ejecutarse en transaccion.

### B-08 - Base de datos y migraciones

- Migraciones idempotentes y reversibles.
- Nombres de tablas en `snake_case` plural.
- Documentar enums/estados con comentarios cuando aplique.
- Cuando no existen datos productivos o se trabaja con `migrate:fresh`, consolidar cambios de esquema directamente en migraciones `create_*`.
- En ese escenario, eliminar migraciones intermedias `add_*`/`drop_*` para mantener historial limpio y coherente.
- Evitar columnas duplicadas para el mismo concepto; usar una columna unica + discriminador de tipo cuando aplique.

## 3. Reglas de Frontend (Vue)

### F-01 - Arquitectura de modulo

- Estructura minima por modulo:
	- `index.vue`
	- `Filtro.vue`
	- `Lista.vue`
	- `Formulario.vue`
	- `Ver.vue` o `Contenido.vue` (si aplica)

### F-02 - Store y estado

- Preferir PMSG para CRUD estandar.
- Stores manuales solo para flujos complejos justificados (ej. bandejas con UI compuesta).
- Pinia es la unica fuente de verdad.
- Evitar duplicar estado en `ref` locales sin motivo claro.

### F-03 - Query, filtros y orden

- Paginacion y ordenamiento deben resolverse en backend.
- Evitar ordenar datasets en cliente cuando exista endpoint listable.
- Reutilizar composables para construir queries (`useQueryBuilder` u otro comun).

### F-04 - Seguridad y permisos

- Toda ruta privada debe requerir auth (`meta.requiresAuth`).
- Acciones visibles en UI deben depender de permisos del `authStore`.
- No exponer acciones de administrador a usuarios de casilla.
- Frontend es la capa oficial de autorizacion por rol/permiso en este servicio.

### F-05 - Consumo de API

- Toda request privada via `ApiService` con `Authorization: Bearer <token>`.
- No duplicar cliente HTTP por modulo sin justificacion tecnica.

### F-06 - Calidad de componentes

- Evitar `console.log` en codigo productivo.
- Evitar estilos inline salvo casos puntuales justificados.
- Reutilizar componentes comunes (`TablaBackend`, `FiltroBackend`, botones de accion).

## 4. Contratos y datos

### D-01 - Compatibilidad de contrato

- No romper contratos vigentes sin plan de migracion documentado.
- Toda modificacion de payload debe reflejarse en `docs/02_contratos.md`.

### D-02 - Tipos y formatos

- IDs siempre enteros.
- Fechas en formato ISO (`YYYY-MM-DD` o datetime ISO).
- Campos opcionales deben estar documentados como opcionales.

### D-03 - Mensajeria con adjuntos/referencias

- `archivo_ids` y `sgd_referencias` son opcionales e independientes.
- Si ambos estan vacios, el mensaje debe seguir siendo valido mientras cumpla las reglas de negocio.

## 5. Estructura y nomenclatura

- Endpoint API: `kebab-case` plural (ej. `/mensajes`, `/casillas`).
- Rutas frontend: `kebab-case`.
- Controllers/Models/Resources: PascalCase singular.
- Carpetas de modulo Vue: PascalCase o convención definida por el proyecto, pero consistente.

## 6. Testing minimo obligatorio

- Backend:
	- pruebas de listados paginados,
	- validaciones de actor,
	- envio de mensajes con adjuntos/referencias.
- Frontend:
	- pruebas funcionales de bandeja, enviados y casillas,
	- validacion de restricciones por rol/permiso.
- Sin evidencia minima de pruebas, un release requiere aprobacion explicita de Tech Lead.

## 7. Reglas de Git y PR

- Commits claros y atomicos (preferible formato convencional).
- Un PR debe resolver un objetivo funcional concreto.
- No mezclar refactor masivo con cambio funcional sensible sin plan por fases.

## 8. Checklist de revision

- [ ] Listados con `paginate()` y limite `max 100`.
- [ ] `per_page` default `10`.
- [ ] Respuestas via Resource, no modelo directo.
- [ ] Reglas por actor/permiso aplicadas en frontend y documentadas en contratos.
- [ ] Mensajes soportan `archivo_ids` y `sgd_referencias` segun contrato.
- [ ] Migraciones consolidadas en `create_*` cuando el proyecto no requiere preservar datos historicos.
- [ ] Todo codigo nuevo incluye comentarios descriptivos por funcion y por bloque logico.
- [ ] Sin `console.log` productivo.
- [ ] Contratos/documentacion actualizados.

## 9. Definicion de hecho

Una tarea se considera completa cuando:
- codigo implementado,
- comportamiento validado,
- pruebas minimas ejecutadas,
- documentacion canonica actualizada.
