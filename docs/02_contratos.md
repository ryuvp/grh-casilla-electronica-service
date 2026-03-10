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
      "titular_tipo": 1,
      "titular_id": 25,
      "tipo_nombre": "Usuario",
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
  "titular_tipo": 1,
  "titular_id": 25,
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

### POST `/mensajes`
Crea mensaje y opcionalmente adjuntos.

Regla de negocio:
- El destino debe ser un usuario con casilla activa.
- El mensaje puede contener archivos digitales y/o referencias externas (SGD, normatividad).

Body:
```json
{
  "asunto": "Asunto",
  "contenido": "Contenido del mensaje",
  "prioridad": 3,
  "usuario_destino_id": 17,
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
- Backend no consulta permisos para ACL; valida token y consistencia del dato.

### POST `/mensajes/{mensaje}/leido`
Marca mensaje como leido.

### DELETE `/mensajes/{mensaje}`
Elimina un mensaje de forma logica (soft delete).

Regla de negocio:
- Solo el usuario origen del mensaje puede eliminarlo.

Notas:
- No se elimina fisicamente el registro.
- El registro mantiene trazabilidad mediante `deleted_at`.

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
