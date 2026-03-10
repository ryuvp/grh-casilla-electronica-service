# CONTEXTO CANONICO - MODULO CASILLAS Y MENSAJES

## 1. Alcance

Este contexto describe el flujo funcional principal de:
- gestion de casillas,
- bandeja de mensajes recibidos,
- bandeja de mensajes enviados,
- marcacion de lectura, adjuntos digitales y referencias a SGD.

Actores principales:
- Administrador de casillas: administra casillas y envios de mensajes.
- Usuario de casilla: solicita apertura y consume sus mensajes.

Ciclo de negocio:
1. Usuario solicita apertura de casilla.
2. Administrador activa la casilla.
3. Usuario con casilla activa puede leer mensajes.
4. Administrador crea y envia mensajes a usuarios con casilla activa.

## 2. Flujo backend

1. Request llega a ruta API protegida por `remoteauth`.
2. Middleware valida token contra Auth Service.
3. Controller procesa validacion de datos y negocio (sin ACL por permisos).
4. Modelo aplica filtros (`Filterable`) y consultas.
5. Response se transforma via `JsonResource`.

## 3. Flujo frontend

1. Router valida token/permisos con `authStore`.
2. Modulo carga store y componentes (`Filtro`, `Lista`, `Contenido/Formulario`).
3. Store consume ApiService con Bearer token.
4. UI renderiza resultados y operaciones de usuario.

## 4. Entidades y relaciones

- `Casilla`: entidad de configuracion de buzones.
- `Mensaje`: entidad de comunicacion entre usuarios.
- `Adjunto`: relacion N:1 con `Mensaje` por `mensaje_id`.

## 5. Contratos clave

- Listados de casillas y mensajes.
- Creacion de mensajes con `archivo_ids` y `sgd_referencias` opcionales.
- Marcado de mensaje como leido.

## 6. Reglas de implementacion futura

- Converger frontend al patron modular estandar de sgd.
- Asegurar paginacion obligatoria en todos los listados.
- Mantener documentacion y contexto sincronizados por cada cambio.
- Mantener validaciones de actor/permiso en frontend y validaciones de datos/estado en backend.

## 6.1 Convencion de autorizacion vigente

- Permisos y roles funcionales se gestionan en frontend.
- Backend no aplica ACL por permisos del Auth Service.
- Backend valida token, integridad de datos y reglas de negocio del dominio.

## 7. Archivos de referencia

- `docs/01_arquitectura.md`
- `docs/02_contratos.md`
- `docs/03_reglas_de_codigo.md`
- `context-general.md`
- `context-backend.md`
- `context-frontend.md`
- `context-new-module.md`
