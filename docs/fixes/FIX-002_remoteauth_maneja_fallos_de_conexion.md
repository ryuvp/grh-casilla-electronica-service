# FIX-002 - RemoteAuth maneja fallos de conexion con auth service

Observacion
- El middleware `RemoteAuth` solo capturaba `RequestException`.
- Ante fallos reales de conectividad hacia auth service podia propagarse un error no controlado.

Propuesta de solucion
- Capturar explicitamente `ConnectionException` y responder `503` consistente.

Solucion implementada
- Se agrego manejo de `ConnectionException` en `RemoteAuth`.
- El middleware responde `503` con mensaje tecnico controlado cuando auth service no es alcanzable.

Archivos modificados
- backend/app/Http/Middleware/RemoteAuth.php

Fecha
- 2026-03-31
