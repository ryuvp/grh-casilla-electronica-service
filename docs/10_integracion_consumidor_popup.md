# Guía paso a paso — integración del popup de comunicados en otros servicios GRH

## Objetivo

Esta guía explica cómo integrar el visualizador de comunicados del `grh-communication-service` dentro de cualquier frontend del ecosistema GRH, por ejemplo:

- `grh_sgd_service`
- `grh-auth-service`
- `grh-mvp-service`
- `grh-casilla-electronica-service`

La integración está pensada para ser:

- rápida
- replicable
- poco invasiva
- desacoplada del resto del servicio de comunicados

El resultado esperado es que, después de validar la sesión del usuario, el sistema consumidor consulte si existen comunicados vigentes para su servicio y, si los hay, los muestre en un overlay fullscreen.

---

## Qué hace exactamente la integración

El servicio consumidor:

1. valida token y sesión
2. consulta al `grh-communication-service`
3. pide los comunicados visibles para su código de servicio
4. obtiene metadatos del archivo desde `grh-file-service`
5. muestra cada comunicado en un overlay
6. registra la visualización de cada comunicado

No crea comunicados. No administra comunicados. Solo los consume y los visualiza.

---

## Servicios involucrados

Para que funcione el popup deben existir estos servicios:

1. `grh-communication-service`
2. `grh-file-service`
3. el servicio consumidor, por ejemplo `sgd`
4. el servicio de autenticación si el frontend lo usa para bootstrap de sesión

---

## Endpoints que consume el integrador

### Communication service

El frontend consumidor usa:

1. `GET /api/comunicados/visibles?servicio=CODIGO`
2. `POST /api/comunicados/{id}/registrar-vista`

Ejemplo:

```http
GET /api/comunicados/visibles?servicio=SGD
POST /api/comunicados/15/registrar-vista
```

Payload del registro:

```json
{
  "servicio": "SGD"
}
```

### File service

Para resolver la vista previa del archivo:

1. `GET /api/files/info?ids[]=1&ids[]=2`
2. `GET /api/files/visualizar/{id}`

---

## Variables `.env` requeridas en el servicio consumidor

Agregar en el frontend consumidor:

```env
VITE_COMUNICADOS_API=http://localhost:8087/api
VITE_FILE_API=http://localhost:8085
```

En producción:

```env
VITE_COMUNICADOS_API=https://api-comunicados.regionhuanuco.gob.pe/api
VITE_FILE_API=https://files.regionhuanuco.gob.pe
```

Notas:

- `VITE_COMUNICADOS_API` debe terminar en `/api`
- `VITE_FILE_API` no debe terminar en `/api`, porque el store arma internamente la ruta `/api/files/visualizar/{id}`

---

## Código de servicio

Cada servicio consumidor debe identificarse con un código fijo.

Ejemplos:

- `SGD`
- `AUTH`
- `MVP`
- `CASILLA`

Ese código debe existir también en los destinos configurados para cada comunicado.

Ejemplo dentro del store:

```js
const SERVICIO_CODIGO = 'SGD'
```

Si integras en otro servicio, solo debes cambiar ese valor.

---

## Archivos mínimos que se deben llevar al servicio consumidor

La integración mínima requiere estos archivos:

1. `frontend/src/components/comunicados/ComunicadosOverlay.vue`
2. `frontend/src/stores/comunicados/comunicadosStore.js`

Además, se debe tocar:

3. `frontend/src/core/services/index.js`
4. `frontend/src/App.vue`

En algunos proyectos también hay que retirar un modal o integración previa si ya existía otra solución.

---

## Paso 1 — agregar el servicio HTTP de comunicados

Editar:

`frontend/src/core/services/index.js`

Agregar:

```js
export const ComunicadosService = createApiService(
  import.meta.env.VITE_COMUNICADOS_API || 'http://localhost:8087/api'
)
```

Si ya existe otra integración antigua, como `PubService`, no la mezcles con esta. Lo correcto es dejar un servicio específico para comunicados.

---

## Paso 2 — crear el store consumidor

Crear:

`frontend/src/stores/comunicados/comunicadosStore.js`

Contenido base:

```js
import { defineStore } from 'pinia'
import { ComunicadosService, FileService } from '@/core/services'

const SERVICIO_CODIGO = 'SGD'

function buildVisualizarUrl(fileId) {
  if (!fileId) return ''
  const base = (import.meta.env.VITE_FILE_API || 'http://localhost:8085').replace(/\/$/, '')
  return `${base}/api/files/visualizar/${fileId}`
}

function normalizarTipoArchivo(mimeType = '') {
  if (mimeType === 'application/pdf') return 'pdf'
  if (mimeType.startsWith('image/')) return 'image'
  return ''
}

const useComunicadosStore = defineStore('comunicados-visibles', {
  state : () => ({
    items   : [],
    loading : false,
    loaded  : false,
  }),

  actions : {
    async cargarVisibles() {
      this.loading = true

      try {
        const response = await ComunicadosService.get('/comunicados/visibles', {
          servicio : SERVICIO_CODIGO,
        })

        const comunicados = response.data?.data || []
        const ids = [...new Set(comunicados.map(item => item.archivo_id).filter(Boolean))]
        let metadata = {}

        if (ids.length) {
          const filesResponse = await FileService.get('/files/info', { ids })
          metadata = Object.fromEntries(
            (filesResponse.data?.archivos || []).map(archivo => [archivo.id, archivo])
          )
        }

        this.items = comunicados.map((item) => {
          const archivo = metadata[item.archivo_id] || {}

          return {
            ...item,
            archivo_nombre : archivo.nombre_original || archivo.ruta_relativa || '',
            archivo_tipo   : normalizarTipoArchivo(archivo.mime_type || ''),
            archivo_url    : buildVisualizarUrl(item.archivo_id),
          }
        })

        this.loaded = true
        return this.items
      } finally {
        this.loading = false
      }
    },

    async registrarVista(comunicadoId) {
      if (!comunicadoId) return null

      return ComunicadosService.post(`/comunicados/${comunicadoId}/registrar-vista`, {
        servicio : SERVICIO_CODIGO,
      })
    },
  },
})

export default useComunicadosStore
```

Qué debes adaptar aquí:

- `SERVICIO_CODIGO`

Qué no debes tocar sin necesidad:

- estructura del payload
- uso de `FileService`
- forma en que se construye `archivo_url`

---

## Paso 3 — crear el componente visualizador

Crear:

`frontend/src/components/comunicados/ComunicadosOverlay.vue`

Este componente:

- se teletransporta al `body`
- ocupa toda la pantalla
- muestra imagen o PDF
- permite avanzar entre comunicados
- registra la visualización una sola vez por elemento

Debe exponer un método `cargar()` mediante `defineExpose`.

Estructura esperada:

```vue
<script setup>
import { computed, ref } from 'vue'
import useComunicadosStore from '@/stores/comunicados/comunicadosStore.js'

const store = useComunicadosStore()
const visible = ref(false)
const indiceActual = ref(0)
const vistos = ref(new Set())

const total = computed(() => store.items.length)
const actual = computed(() => store.items[indiceActual.value] || null)

async function cargar() {
  await store.cargarVisibles()
  if (store.items.length) {
    indiceActual.value = 0
    visible.value = true
    await registrarActual()
  }
}

defineExpose({ cargar })
</script>
```

La implementación final puede tener más estilos o navegación, pero el contrato importante es:

- `cargar()`
- `store.cargarVisibles()`
- `store.registrarVista()`

---

## Paso 4 — montarlo en `App.vue`

Editar:

`frontend/src/App.vue`

Agregar import:

```js
import ComunicadosOverlay from '@/components/comunicados/ComunicadosOverlay.vue'
```

Agregar ref:

```js
const comunicadosOverlayRef = ref(null)
```

Agregar el componente en el template principal:

```vue
<RouterView />
<ComunicadosOverlay ref="comunicadosOverlayRef" />
```

Importante:

- debe vivir cerca de la raíz de la app
- no debe colgar de un módulo específico
- no debe depender de una ruta concreta

---

## Paso 5 — dispararlo después de validar sesión

Este es el punto más importante.

No debes cargar el popup:

- antes de validar token
- antes de tener usuario autenticado
- antes de que la app termine su bootstrap principal

El momento correcto es inmediatamente después de una validación de sesión exitosa.

Ejemplo:

```js
const isValid = await authStore.validateToken()

if (!isValid) {
  return false
}

finalizeBootstrap()
await nextTick()
await comunicadosOverlayRef.value?.cargar()
```

En otras palabras:

1. validar sesión
2. terminar bootstrap
3. esperar render
4. cargar comunicados

---

## Paso 6 — retirar integraciones antiguas si existen

Si el servicio ya tenía una integración previa de publicaciones, debes retirarla para evitar duplicidad.

Ejemplos de restos antiguos:

- `ModalPublicaciones.vue`
- `stores/publicaciones/publicacionesStore.js`
- `PubService`
- variables `.env` tipo `VITE_PUB_API`

No mezclar:

- popup viejo de publicaciones
- popup nuevo de comunicados

Debe quedar una sola solución activa.

---

## Paso 7 — validar con datos reales

Antes de dar por terminada la integración, validar este flujo:

1. el usuario inicia sesión
2. el frontend carga normalmente
3. se consulta `GET /api/comunicados/visibles?servicio=...`
4. si hay comunicados, aparece el overlay
5. al avanzar o cerrar, se registra `registrar-vista`
6. si no hay comunicados, la app no muestra nada y sigue normal

También verificar:

- imágenes
- PDFs
- archivos sin vista previa
- varios comunicados en secuencia

---

## Paso 8 — checklist técnico

Checklist mínimo:

1. `VITE_COMUNICADOS_API` configurado
2. `VITE_FILE_API` configurado
3. `ComunicadosService` agregado
4. `comunicadosStore.js` creado
5. `ComunicadosOverlay.vue` creado
6. overlay montado en `App.vue`
7. carga disparada después de `validateToken()`
8. integración antigua eliminada
9. prueba con comunicados reales aprobada

---

## Errores comunes

### 1. El popup no aparece nunca

Revisar:

- si `validateToken()` realmente está retornando `true`
- si `comunicadosOverlayRef.value?.cargar()` se ejecuta
- si el servicio tiene comunicados vigentes para ese código

### 2. La consulta responde vacío

Revisar:

- valor de `SERVICIO_CODIGO`
- destinos configurados en `grh-communication-service`
- fechas de vigencia del comunicado
- estado `activo`

### 3. El popup aparece pero no muestra archivo

Revisar:

- `archivo_id`
- respuesta de `GET /api/files/info`
- MIME type del archivo
- `VITE_FILE_API`

### 4. El popup sale dos veces

Revisar:

- que no esté montado en más de un layout
- que no siga existiendo otro modal anterior
- que no se esté llamando `cargar()` más de una vez durante bootstrap

### 5. Falla la visualización del PDF o imagen

Revisar:

- CORS de `file-service`
- endpoint `/api/files/visualizar/{id}`
- permisos del archivo en `file-service`

---

## Recomendación de diseño para replicarlo en otros servicios

Para mantener la integración simple:

1. no tocar el backend consumidor
2. no crear tablas locales nuevas
3. no mezclar la administración de comunicados con su visualización
4. dejar toda la lógica del popup encapsulada en:
   - un store
   - un componente
   - una llamada en `App.vue`

Ese patrón permite replicarlo muy rápido en otros servicios.

---

## Servicios donde este patrón aplica bien

Este enfoque es adecuado para:

- `sgd`
- `auth`
- `mvp`
- `casilla`
- cualquier otro frontend que valide usuario y tenga un bootstrap inicial

No conviene usarlo tal cual en:

- servicios sin frontend
- procesos batch
- apps que no tienen sesión de usuario

---

## Recomendación final

No hacer merge de ramas completas que mezclen popup con otros cambios de negocio.  
Lo correcto es portar solo el bloque mínimo de integración descrito en esta guía.

Eso mantiene:

- trazabilidad
- facilidad de soporte
- replicabilidad
- menor riesgo al desplegar
