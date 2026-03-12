# Historial de fixes

Este directorio almacena el historial tecnico de bugs resueltos.

## Regla de nombrado

Formato obligatorio:

`FIX-XXX_nombre_corto_del_bug.md`

Ejemplos:
- `FIX-001_refactorizacion_ts_a_js.md`
- `FIX-002_inconsistencia_del_store.md`
- `FIX-003_cambio_en_el_diseno.md`

## Regla de numeracion

1. Buscar el mayor correlativo existente en archivos `FIX-XXX_*.md`.
2. Incrementar en `+1`.
3. Si no existe ninguno, iniciar con `FIX-001`.

## Estructura minima obligatoria

Cada archivo de fix debe contener:

- `FIX-XXX - Nombre del problema`
- `Observacion`
- `Propuesta de solucion`
- `Solucion implementada`
- `Archivos modificados`
- `Fecha` (`YYYY-MM-DD`)

## Regla de no duplicidad

- No duplicar fixes ya documentados.
- Si el bug ya tiene fix, agregar referencia al fix existente.
