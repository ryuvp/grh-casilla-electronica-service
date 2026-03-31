# FIX-003 - Contrato de mensajes destacados y archivados documentado

Observacion
- El backend y frontend ya manejaban bandejas y acciones de `destacados` y `archivados`, pero `docs/02_contratos.md` no documentaba esos endpoints.

Propuesta de solucion
- Actualizar el contrato canonico para reflejar las rutas existentes y sus reglas de negocio.

Solucion implementada
- Se documentaron `GET /mensajes/destacados`, `GET /mensajes/archivados`, `POST /mensajes/{mensaje}/destacar` y `POST /mensajes/{mensaje}/archivar`.

Archivos modificados
- docs/02_contratos.md

Fecha
- 2026-03-31
