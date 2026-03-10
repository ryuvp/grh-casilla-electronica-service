# 07 - Runbook de operacion y release

Estado: Vigente  
Owner: Tech Lead + DevOps  
Ultima actualizacion: 2026-03-09  
Proxima revision: 2026-04-09

## 1. Checklist de pre-release

1. Imagenes backend/frontend construidas correctamente.
2. Variables de entorno verificadas (`AUTH_SERVICE_URL`, URLs frontend).
3. Migraciones aplicadas en entorno destino.
4. Smoke tests de endpoints criticos ejecutados.
5. Validacion de permisos y rutas protegidas.

## 2. Smoke tests minimos

- `GET /api/casillas` con token valido.
- `POST /api/mensajes` con payload minimo valido.
- `GET /api/mensajes/entrada` para usuario con datos.
- Navegacion frontend: `bandeja`, `enviados`, `casillas`.

## 3. Plan de rollback

1. Revertir deployment a ultima imagen estable.
2. Verificar conectividad con DB y Auth Service.
3. Re-ejecutar smoke tests.
4. Publicar reporte de incidente con causa raiz inicial.

## 4. Monitoreo operativo

- Logs de backend por errores 5xx.
- Errores de autenticacion remota (401/503).
- Tiempos de respuesta en listados de mensajes/casillas.

## 5. Post-release

- Confirmar estado en 15 min, 1 hora y 24 horas.
- Registrar hallazgos en bitacora (`09_bitacora_inicial_y_alcance.md`).
