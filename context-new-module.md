# GUIA - CREAR UN NUEVO MODULO EN CASILLA (metodologia auth/sgd)

## 1. Objetivo

Crear un modulo CRUD completo y consistente en backend + frontend.

## 2. Backend (pasos)

1. Crear modelo en `backend/app/Models` con:
   - `fillable`
   - `filters`
   - `validables`
   - `casts` si aplica
2. Crear `Resource` en `backend/app/Http/Resources`.
3. Crear controller con metodos:
   - `index` (siempre paginado)
   - `store`
   - `show`
   - `update`
   - `destroy`
   - Nota: no implementar ACL por permisos en backend; solo validacion de token y reglas de negocio.
4. Registrar ruta en `backend/routes/api.php` dentro de `remoteauth`.
5. Documentar endpoint en `docs/02_contratos.md`.

## 3. Frontend (pasos)

1. Crear config PMSG en `frontend/src/core/models/config`.
2. Registrar modelo en `frontend/src/core/models/index.js`.
3. Crear store en `frontend/src/stores/<modulo>/`.
4. Crear modulo en `frontend/src/modules/<Modulo>/`:
   - `index.vue`
   - `Filtro.vue`
   - `Lista.vue`
   - `Formulario.vue`
   - `Ver.vue` (opcional)
5. Crear/ajustar vista en `frontend/src/views`.
6. Registrar ruta en `frontend/src/router/index.js` con permisos.

## 4. Reglas obligatorias

- `per_page` default: `10`.
- maximo por request: `100`.
- ordenamiento desde backend (`orders[]=campo.direccion`).
- no romper contratos sin actualizar `docs/02_contratos.md`.

## 5. Checklist final

- Backend compila y responde CRUD.
- Frontend muestra lista, filtra, crea y actualiza.
- Permisos aplicados por ruta y accion en frontend.
- Documentacion canonica actualizada.
