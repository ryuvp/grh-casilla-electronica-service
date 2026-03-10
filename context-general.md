# CONTEXTO GENERAL - GRH Casilla Electronica Service

## 1. Proposito

Microservicio del ecosistema GRH para gestion de casillas y mensajes internos entre usuarios/dependencias.

## 2. Componentes

- Backend: Laravel 10 (PHP 8.2) - `backend/`
- Frontend: Vue 3 + Vite - `frontend/`
- Database: PostgreSQL 17
- Orquestacion: Docker Compose

## 3. Puertos y contenedores

- Backend nginx: `8087` (`casilla_electronica_service_web`)
- Backend php-fpm: `9005` (`casilla_electronica_service_app`)
- Frontend Vite: `5178` (`casilla_electronica_service_frontend`)
- PostgreSQL: `5439` (`casilla_electronica_service_db`)

## 4. Integraciones externas

- Auth Service para validacion de token (`AUTH_SERVICE_URL`).
- File Service para metadatos/descarga de adjuntos (`VITE_API_FILE`).

## 5. Modulos funcionales frontend

- Casillas
- Bandeja de entrada
- Bandeja de enviados

## 6. Entidades backend

- `Casilla`
- `Mensaje`
- `Adjunto`
- `Logs` (registro tecnico)

## 7. Convenciones objetivo (alineadas a auth/sgd)

- Contratos API documentados y versionables.
- Paginacion obligatoria en listados.
- Ordenamiento backend-driven.
- Documentacion canonica viva en `docs/`.
- Permisos/roles funcionales gestionados en frontend; backend sin ACL por permisos.

## 8. Riesgos actuales

- Implementacion heterogenea en frontend (PMSG + store manual).
- Flujos de usuario autenticado no totalmente uniformes.
- Endpoints con comportamiento de paginacion mixto.
