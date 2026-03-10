# 05 - Reglas para IA

Estado: Vigente  
Owner: Tech Lead + Equipo de desarrollo  
Ultima actualizacion: 2026-03-09  
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
- Frontend:
  - Mantener patron modular por funcionalidad.
  - Evitar logica duplicada en componentes.

## 4. Regla de actualizacion documental

Si se cambia comportamiento funcional, la IA debe actualizar:
- contratos,
- reglas/procedimientos,
- y contexto correspondiente.

## 5. Prohibiciones

- No borrar historial documental sin marcar deprecacion.
- No introducir deuda tecnica deliberada sin anotarla en bitacora.
