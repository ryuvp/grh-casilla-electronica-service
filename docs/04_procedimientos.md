# 04 - Procedimientos

Estado: Vigente  
Owner: Equipo Backend + Frontend  
Ultima actualizacion: 2026-03-09  
Proxima revision: 2026-04-09

## 1. Levantar entorno local

Prerequisito:
- Docker y Docker Compose.
- Red externa `grh_network` creada.

Pasos:
1. `docker network create grh_network` (solo una vez).
2. `docker compose up --build -d`.
3. Entrar a backend y ejecutar setup inicial:
   - `docker exec -it casilla_electronica_service_app bash`
   - `./setup.sh`
4. Frontend:
   - servicio `casilla_electronica_service_frontend` ejecuta `npm install && npm run dev`.

## 2. Verificacion rapida de salud

- API: `GET http://localhost:8087/api/casillas` con token valido.
- Frontend: `http://localhost:5178`.
- DB: puerto `5439` disponible.

## 3. Flujo de cambio backend

1. Implementar cambio.
2. Actualizar validaciones/resources/contratos.
3. Ejecutar pruebas del modulo afectado.
4. Actualizar `docs/02_contratos.md` si cambia payload.
5. Registrar decision en bitacora si afecta alcance.

## 4. Flujo de cambio frontend

1. Implementar en modulo correspondiente.
2. Verificar permisos, carga y errores.
3. Validar paginacion/orden server-side.
4. Actualizar contratos o procedimientos si cambia interaccion.

## 5. Checklist pre-PR

- Compila backend y frontend.
- Sin errores criticos en consola.
- Sin endpoints rotos del modulo afectado.
- Documentacion actualizada.
