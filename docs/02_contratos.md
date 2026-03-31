# 02 - Contratos de API

Estado: Vigente  
Owner: Equipo Backend  
Ultima actualizacion: 2026-03-09  
Proxima revision: 2026-04-09

## 1. Convenciones globales

- Base URL local: `http://localhost:8087/api`.
- Formato: JSON sobre HTTP.
- Auth: `Authorization: Bearer <token>` en rutas protegidas.
- Autorizacion funcional (roles/permisos): se gestiona en frontend.
- El backend no evalua ACL por permisos del Auth Service; aplica validaciones de token y reglas de negocio de datos.
- Exitos de lectura: `200`.
- Creacion: `201` o `200` segun implementacion actual.
- Error de validacion: `400` con `errors`.
- Error de autorizacion: `401/403`.

## 1.1 Reglas de actores (negocio en frontend)

- Administrador de casillas:
  - Puede listar usuarios y casillas.
  - Puede activar casillas solicitadas.
  - Puede crear y enviar mensajes a usuarios con casilla activa.
- Usuario de casilla:
  - Puede solicitar apertura de casilla.
  - Solo puede leer sus mensajes cuando su casilla esta activa.

## 2. Casillas

### GET `/casillas`
Lista casillas con filtros.

Query soportada (estado actual):
- `per_page`, `page`
- `search`, `searchField`
- `dateStart`, `dateEnd`, `dateField`
- `orders[]` (ejemplo: `orders[]=id.asc`)
- `with`, `select`

Response ejemplo:
```json
{
  "data": [
    {
      "id": 1,
      "numero": "CAS-001",
      "designacion_id": 25,
      "activo": true,
      "fecha_inicio": "2026-01-01",
      "fecha_fin": null
    }
  ]
}
```

### POST `/casillas`
Crea una casilla.

Body:
```json
{
  "numero": "CAS-001",
  "designacion_id": 25,
  "activo": true,
  "fecha_inicio": "2026-01-01",
  "fecha_fin": null
}
```

### GET `/casillas/{casilla}`
Devuelve detalle de una casilla.

### PUT `/casillas/{casilla}`
Actualiza una casilla.

Regla de negocio:
- La activacion de casilla (campo `activo=true`) corresponde al administrador de casillas en el flujo de frontend.

### DELETE `/casillas/{casilla}`
Elimina una casilla de forma logica (soft delete).

Notas:
- No se elimina fisicamente el registro.
- El registro mantiene trazabilidad mediante `deleted_at`.

## 3. Mensajes

### GET `/mensajes/entrada`
Lista mensajes recibidos del usuario autenticado.

### GET `/mensajes/enviados`
Lista mensajes enviados por el usuario autenticado.

### GET `/mensajes/destacados`
Lista mensajes destacados recibidos por el usuario autenticado.

Regla de negocio:
- Solo devuelve mensajes de la casilla destino autenticada.
- Solo incluye mensajes con `destacado=true` y `archivado=false`.

### GET `/mensajes/archivados`
Lista mensajes archivados recibidos por el usuario autenticado.

Regla de negocio:
- Solo devuelve mensajes de la casilla destino autenticada.
- Solo incluye mensajes con `archivado=true`.

### POST `/mensajes`
Crea mensaje y opcionalmente adjuntos.

Regla de negocio:
- El destino debe ser una casilla activa.
- El mensaje puede contener archivos digitales y/o referencias externas (SGD, normatividad).
- El envio es unidireccional (notificacion): no existe respuesta del destinatario.
- Solo perfiles con rol `admin` o `notificador` pueden crear/enviar mensajes.

Body:
```json
{
  "asunto": "Asunto",
  "contenido": "Contenido del mensaje",
  "prioridad": 3,
  "casilla_destino_id": 17,
  "archivo_ids": [101, 102],
  "sgd_referencias": [
    {
      "documento_id": 9001,
      "tipo": "documento"
    },
    {
      "documento_id": 778,
      "tipo": "archivo"
    }
  ],
  "normatividad_referencias": [
    {
      "normatividad_id": 501
    }
  ]
}
```

Notas:
- `archivo_ids` es opcional para adjuntos digitales almacenados en File Service.
- `sgd_referencias` es opcional para vinculos a documentos/archivos existentes en SGD.
- `normatividad_referencias` es opcional para vinculos a normativas externas.
- Persistencia: todas las referencias se almacenan en tabla `adjuntos` con una sola columna de identificador externo (`referencia_id`) y una columna de discriminador (`tipo`).

### GET `/mensajes/{mensaje}`
Detalle de mensaje (si pertenece al usuario).

Regla de negocio:
- El usuario de casilla solo puede leer mensajes propios.

Nota de autorizacion:
- Restricciones por rol/permiso se aplican en frontend.
- Backend no consulta permisos para ACL; valida token, casilla activa y consistencia del dato.

### Modelo de actores para mensajeria

- Casillas: se relacionan por `designacion_id`.
- Mensajes: usan `casilla_origen_id` y `casilla_destino_id`.
- Un usuario puede operar multiples casillas si tiene multiples designaciones activas.
- El destinatario solo puede leer y marcar lectura del mensaje recibido.

### POST `/mensajes/{mensaje}/leido`
Marca mensaje como leido.

### POST `/mensajes/{mensaje}/destacar`
Alterna el estado destacado de un mensaje recibido.

Regla de negocio:
- Solo la casilla destino del mensaje puede ejecutar esta accion.

### POST `/mensajes/{mensaje}/archivar`
Alterna el estado archivado de un mensaje recibido.

Regla de negocio:
- Solo la casilla destino del mensaje puede ejecutar esta accion.

### DELETE `/mensajes/{mensaje}`
Operacion reservada (sin uso operativo actual).

Regla de negocio:
- En el modelo actual de notificaciones unidireccionales no se permite eliminar mensajes.

Notas:
- Se conserva como registro tecnico para una posible evolucion futura.
- La API responde `405 Method Not Allowed`.

### PUT/PATCH `/mensajes/{mensaje}`
Operacion reservada (sin uso operativo actual).

Notas:
- En el modelo actual de notificaciones unidireccionales no se permite editar mensajes.
- Se conserva como registro tecnico para una posible evolucion futura.
- La API responde `405 Method Not Allowed`.

## 4. Errores estandar

Error de validacion:
```json
{
  "status": "error",
  "errors": {
    "campo": ["Mensaje de error"]
  }
}
```

Error de negocio:
```json
{
  "status": "error",
  "message": "Descripcion"
}
```

## 5. Pendientes de normalizacion

- Unificar respuestas paginadas para todos los listados.
- Eliminar parametros legados no estandar (`limit`, `hash`) una vez migrado frontend.
- Alinear ordenamiento a `orders[]=campo.direccion` de forma consistente.
