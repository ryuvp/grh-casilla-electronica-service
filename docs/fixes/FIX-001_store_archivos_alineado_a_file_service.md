# FIX-001 - Store de archivos alineado a contrato de file service

Observacion
- El frontend de casilla consumia `grh-file-service` con rutas/metodos desalineados.
- `marcar-permanente` se llamaba con `PUT` y la descarga se construia como `/{id}/download`, mientras el file service expone `POST /marcar-permanente` y `GET /download/{id}`.

Propuesta de solucion
- Ajustar el store frontend para consumir el contrato real del microservicio de archivos.

Solucion implementada
- `filesStore.js` ahora usa `POST /marcar-permanente`.
- `filesStore.js` ahora descarga mediante `/download/{id}`.

Archivos modificados
- frontend/src/stores/files/filesStore.js

Fecha
- 2026-03-31
