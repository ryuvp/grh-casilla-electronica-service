# 05 - Reglas para IA

Estado: Vigente  
Owner: Tech Lead + Equipo de desarrollo  
Ultima actualizacion: 2026-03-12  
Proxima revision: 2026-04-09

## 1. Alcance

Estas reglas aplican a cualquier asistente IA que trabaje en este repositorio.

## 2. Reglas obligatorias

1. No inventar endpoints, campos ni contratos.
2. Leer primero `docs/02_contratos.md`, `docs/03_reglas_de_codigo.md` y `context*.md`.
3. Mantener compatibilidad con metodologia usada en auth/sgd.
4. No refactorizar mas alla del alcance pedido.
5. Si hay contradiccion documental, seguir prioridad del `docs/README.md`.
6. Siempre reportar riesgos y supuestos explicitos.

## 3. Reglas para cambios de codigo

- Backend:
  - Mantener resources y validaciones consistentes.
  - Preferir paginacion obligatoria en listados.
  - Si no hay datos que preservar, consolidar esquema en migraciones `create_*` y evitar cadenas largas de `add_*`/`drop_*`.
  - Evitar duplicidad de columnas para el mismo dato; usar identificador unico + `tipo` cuando corresponda.
  - Modelar casillas por `designacion_id` y mensajes por `casilla_origen_id/casilla_destino_id`.
- Frontend:
  - Mantener patron modular por funcionalidad.
  - Evitar logica duplicada en componentes.

## 4. Regla de actualizacion documental

Si se cambia comportamiento funcional, la IA debe actualizar:
- contratos,
- reglas/procedimientos,
- y contexto correspondiente.

## 5. Regla de registro automatico de fixes

Cuando durante una conversacion o tarea de desarrollo se detecte y se resuelva un bug, error tecnico o comportamiento incorrecto, la IA debe documentar el fix automaticamente.

### 5.1 Condicion para crear un fix

La IA debe crear un registro en `docs/fixes` cuando se cumplan las cuatro condiciones:
1. Se identifico un problema o bug.
2. Se analizo la causa del problema.
3. Se propuso una solucion tecnica.
4. Se implemento o se definio claramente la solucion.

### 5.2 Nombre del archivo

Formato obligatorio:

`FIX-XXX_nombre_corto_del_bug.md`

Ejemplos:

- `FIX-001_refactorizacion_ts_a_js.md`
- `FIX-002_inconsistencia_del_store.md`
- `FIX-003_cambio_en_el_diseno.md`

Regla de numeracion:
- Tomar el numero mayor existente en `docs/fixes/FIX-XXX_*.md`.
- Incrementar en `+1`.
- Si no existe ninguno, iniciar en `FIX-001`.

### 5.3 Estructura obligatoria del documento

Cada archivo de fix debe contener:
- `FIX-XXX - Nombre del problema`
- `Observacion`
- `Propuesta de solucion`
- `Solucion implementada`
- `Archivos modificados`
- `Fecha` en formato `YYYY-MM-DD`

### 5.4 Reglas adicionales

- La documentacion debe ser breve, tecnica y clara.
- No duplicar fixes ya documentados.
- Si el bug ya tiene un fix documentado, solo referenciar el fix existente.
- El objetivo es mantener un historial tecnico de bugs resueltos.

## 6. Prohibiciones

- No borrar historial documental sin marcar deprecacion.
- No introducir deuda tecnica deliberada sin anotarla en bitacora.
