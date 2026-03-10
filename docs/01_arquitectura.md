# 01 - Arquitectura del Sistema

Estado: Vigente  
Owner: Equipo Backend + Frontend (Tech Lead responsable)  
Ultima actualizacion: 2026-03-09  
Proxima revision: 2026-04-09

## 1. Vision general

GRH Casilla Electronica Service es un microservicio para gestion de casillas y mensajeria interna del ecosistema GRH.

Actores de negocio:
- Administrador de casillas: gestiona casillas, visualiza usuarios y estado de casillas, activa aperturas solicitadas y puede enviar mensajes.
- Usuario de casilla: solicita apertura de su casilla y, una vez activa, solo puede leer sus mensajes.

Componentes principales:
- Frontend SPA en Vue 3 + Vite (puerto 5178).
- Backend API en Laravel 10 + PHP 8.2 (puerto 8087 via Nginx).
- PostgreSQL 17 (puerto 5439).
- Integracion con Auth Service para validacion de token y con Sgd Service y File Service para adjuntos.

Convencion vigente de autorizacion:
- La gestion de permisos/roles funcionales se realiza en frontend.
- El backend no aplica ACL por permisos del Auth Service; solo valida token y reglas de negocio de datos.

## 2. Arquitectura de alto nivel

- Usuario navega el frontend y consume modulos `bandeja`, `enviados` y `casillas`.
- Frontend usa ApiService para enviar Bearer token.
- Backend valida token por middleware remoto (`remoteauth`).
- Controladores acceden a modelos Eloquent con trait `Filterable`.
- Responses estandarizadas con Resources (`CasillaResource`, `MensajeResource`).

## 2.1 Flujo de negocio

1. El usuario de casilla solicita la apertura de su casilla.
2. El administrador de casillas revisa la solicitud y activa la casilla.
3. Con casilla activa, el usuario puede acceder a su bandeja y leer mensajes.
4. El administrador crea mensajes y los envia a usuarios con casilla activa.
5. Cada mensaje puede incluir:
	- archivos digitales (adjuntos del File Service),
	- referencias a archivos/documentos del SGD.

## 3. Integraciones externas

- Auth Service: validacion de sesion/token y metadatos de usuario.
- File Service: consulta de archivos para mensajes con adjuntos.
- Sgd Service: consulta de documentos referenciados en mensajes.

## 4. Rutas API principales

- `GET /api/casillas`
- `POST /api/casillas`
- `GET /api/casillas/{casilla}`
- `PUT /api/casillas/{casilla}`
- `GET /api/mensajes/entrada`
- `GET /api/mensajes/enviados`
- `POST /api/mensajes`
- `GET /api/mensajes/{mensaje}`
- `POST /api/mensajes/{mensaje}/leido`

## 5. Decisiones tecnicas vigentes

- Mantener enfoque API REST con resources por entidad.
- Mantener filtro server-side con `Filterable`.
- Estandarizar paginacion y ordenamiento backend-driven en siguientes iteraciones.
- Estandarizar frontend hacia patron modular usado en `grh_sgd_service`.
- Mantener en frontend el control de acceso por rol/permiso (UI, rutas y acciones).
- Limitar backend a validacion de token y reglas de negocio (propiedad de datos, estados, consistencia).

## 6. Riesgos tecnicos actuales

- Diferencias de patron entre modulos frontend (PMSG vs store manual).
- Paginacion no uniforme en algunos endpoints.
- Flujo de usuario autenticado no homogeneo en todos los controladores.

## 7. Objetivo de convergencia

Converger a la metodologia de auth/sgd:
- Contratos claros y versionados.
- Paginacion consistente.
- Modulos frontend con patron comun (Filtro, Lista, Formulario, acciones).
- Documentacion viva y trazable.
