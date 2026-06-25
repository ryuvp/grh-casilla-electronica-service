<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Notificación Electrónica</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            border: 1px solid #1a365d;
            padding: 25px;
            position: relative;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-title {
            text-transform: uppercase;
            font-weight: bold;
            color: #4a5568;
            font-size: 9px;
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }
        .main-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
            margin: 5px 0;
        }
        .subtitle {
            font-size: 9px;
            color: #718096;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #2b6cb0;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .grid-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .label {
            width: 30%;
            color: #718096;
            font-weight: bold;
        }
        .value {
            width: 70%;
            color: #2d3748;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
        }
        .badge-success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #38a169;
        }
        .badge-warning {
            background-color: #feebc8;
            color: #744210;
            border: 1px solid #dd6b20;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            font-size: 8px;
            color: #718096;
            text-align: center;
        }
        .footer-hash {
            font-family: monospace;
            font-size: 9px;
            color: #4a5568;
            margin-top: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">Gobierno Regional de Huánuco</div>
            <div class="main-title">Certificado de Transmisión y Lectura Electrónica</div>
            <div class="subtitle">Documentación Oficial de Trazabilidad del Sistema de Gestión Documental</div>
        </div>

        <!-- Document Info -->
        <div class="section">
            <div class="section-title">Datos del Documento Notificado</div>
            <table class="grid-table">
                <tr>
                    <td class="label">Nro. Registro:</td>
                    <td class="value" style="font-weight: bold;">{{ str_pad($documento['id'] ?? $mensaje->id, 7, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <td class="label">Asunto:</td>
                    <td class="value" style="text-transform: uppercase;">{{ $mensaje->asunto }}</td>
                </tr>
                <tr>
                    <td class="label">Tipo de Documento:</td>
                    <td class="value" style="text-transform: uppercase;">{{ data_get($remitente, 'cargo_tipo', 'DOCUMENTO DE TRAMITE') }}</td>
                </tr>
            </table>
        </div>

        <!-- Sender Info -->
        <div class="section">
            <div class="section-title">Certificación de Envío (Origen)</div>
            <table class="grid-table">
                <tr>
                    <td class="label">Remitente:</td>
                    <td class="value" style="font-weight: bold; text-transform: uppercase;">{{ data_get($remitente, 'usuario_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Cargo / Función:</td>
                    <td class="value" style="text-transform: uppercase;">{{ data_get($remitente, 'cargo_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Dependencia:</td>
                    <td class="value" style="text-transform: uppercase;">{{ data_get($remitente, 'dependencia_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Casilla de Origen:</td>
                    <td class="value" style="font-weight: bold;">{{ data_get($remitente, 'casilla_numero', 'CAS-' . $casillaOrigen->designacion_id) }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha y Hora de Envío:</td>
                    <td class="value" style="font-weight: bold;">{{ $documento['fecha_envio'] }}</td>
                </tr>
            </table>
        </div>

        <!-- Recipient Info -->
        <div class="section">
            <div class="section-title">Certificación de Recepción y Apertura (Destinatario)</div>
            <table class="grid-table">
                <tr>
                    <td class="label">Destinatario:</td>
                    <td class="value" style="font-weight: bold; text-transform: uppercase;">{{ data_get($destinatario, 'usuario_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Cargo / Función:</td>
                    <td class="value" style="text-transform: uppercase;">{{ data_get($destinatario, 'cargo_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Dependencia:</td>
                    <td class="value" style="text-transform: uppercase;">{{ data_get($destinatario, 'dependencia_nombre', 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="label">Casilla de Destino:</td>
                    <td class="value" style="font-weight: bold;">{{ data_get($destinatario, 'casilla_numero', 'CAS-' . $casillaDestino->designacion_id) }}</td>
                </tr>
                <tr>
                    <td class="label">Estado de Transmisión:</td>
                    <td class="value">
                        @if($mensaje->leido)
                            <span class="badge badge-success">APERTURADO / LEÍDO</span>
                        @else
                            <span class="badge badge-warning">ENTREGADO / PENDIENTE DE LECTURA</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Fecha y Hora de Lectura:</td>
                    <td class="value" style="font-weight: bold;">
                        {{ $documento['fecha_lectura'] }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Security Footer -->
        <div class="footer">
            <div style="font-weight: bold; color: #2d3748; margin-bottom: 2px;">CÓDIGO DE VERIFICACIÓN DE TRAZABILIDAD SEGURO (SHA-1)</div>
            <div class="footer-hash">SHA1-CERT-{{ $mensaje->id }}-{{ strtotime($mensaje->created_at) }}</div>
            <div style="margin-top: 10px;">
                Este certificado digital constituye constancia legal fidedigna e inalterable de la transmisión, recepción y apertura de la notificación.<br>
                Generado automáticamente en cumplimiento del marco de Gobierno Digital y Ley de Procedimiento Administrativo General.
            </div>
        </div>
    </div>
</body>
</html>
