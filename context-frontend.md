# CONTEXTO FRONTEND - GRH Casilla Electronica Service

## 1. Estructura relevante

- `frontend/src/router/index.js`
- `frontend/src/core/models/config/`
- `frontend/src/stores/`
- `frontend/src/modules/casilla/`
- `frontend/src/modules/bandeja/`
- `frontend/src/modules/enviados/`
- `frontend/src/components/tabla/`

## 2. Arquitectura actual

- Vue 3 + Vite + Pinia.
- ApiService para backend propio y auth.
- Mezcla de patrones:
  - PMSG para `casillas` y `usuarios`.
  - store manual para `mensajes`.

## 3. Modulos

### Casillas
Usa `TablaBackend`, `FiltroBackend`, store de casillas y acciones CRUD.

### Bandeja
Vista de mensajes recibidos con panel izquierdo (lista) y derecho (contenido).

### Enviados
Vista de mensajes enviados con modal de nuevo mensaje.

## 4. Auth y permisos

- `authStore` valida token y expone permisos por tipo.
- Router guard valida sesion y acceso por rutas permitidas.
- Frontend es la capa oficial de autorizacion funcional por rol/permiso en este servicio.
- Las acciones se habilitan/bloquean en UI segun permisos del usuario autenticado.

## 5. Convenciones objetivo (metodologia sgd)

- Patron modulo estandar: `index`, `Filtro`, `Lista`, `Formulario`, `Ver/Contenido`.
- Paginacion y ordenamiento backend-driven.
- Uso de composables comunes para acciones y carga.
- Configuracion PMSG consistente (`per_page=10` por defecto).

## 6. Brechas a cerrar en refactor

- Unificar modelo de store para mensajeria o justificar excepcion.
- Remover ordenamiento cliente cuando deba resolverse en backend.
- Homogeneizar contratos de eventos/props en componentes de tabla/filtros.
