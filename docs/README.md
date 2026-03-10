# Indice de documentacion - grh-casilla-electronica-service

Este indice define la fuente unica de verdad documental para el servicio.

## Documentos canonicos

1. `01_arquitectura.md` - arquitectura backend/frontend e integraciones.
2. `02_contratos.md` - contratos API y convenciones.
3. `03_reglas_de_codigo.md` - estandares de implementacion.
4. `04_procedimientos.md` - operacion local y tareas frecuentes.
5. `05_reglas_ia.md` - reglas para asistencia IA.
6. `06_calidad_documental.md` - rubrica, metricas y SLA documental.
7. `07_runbook_operacion_y_release.md` - checklist de release, rollback y verificacion.
8. `08_glosario.md` - terminos de dominio y tecnicos.
9. `09_bitacora_inicial_y_alcance.md` - alcance inicial, riesgos y decisiones.

## Prioridad de referencia

Si dos documentos se contradicen, usar este orden:

1) `02_contratos.md`
2) `03_reglas_de_codigo.md`
3) `04_procedimientos.md`
4) `01_arquitectura.md`
5) `context*.md`

## Convenciones obligatorias

- Cada documento debe incluir al inicio:
  - `Estado: Vigente | En revision | Deprecado`
  - `Owner:`
  - `Ultima actualizacion:`
  - `Proxima revision:`
- Toda regla nueva debe reflejarse en el documento canonico correspondiente.
- Contenido historico debe marcarse explicitamente como `Deprecado`.

## Regla de calidad

No se considera terminado un cambio si:
- modifica contrato/regla/procedimiento,
- y no actualiza el documento canonico correspondiente.
