<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Lectura de Notificación Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1a202c;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .decenio-header {
            text-align: center;
            font-size: 9px;
            color: #718096;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        .header {
            position: relative;
            margin-bottom: 40px;
            min-height: 80px;
        }
        .title-container {
            width: 60%;
            float: left;
            text-align: center;
            padding-top: 10px;
        }
        .main-title {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        .signature-box {
            width: 250px;
            float: right;
            border: 1px dashed #718096;
            background-color: #f7fafc;
            padding: 8px 12px;
            font-size: 9px;
            color: #2d3748;
            border-radius: 4px;
            box-shadow: 1px 1px 3px rgba(0,0,0,0.05);
        }
        .signature-title {
            font-weight: bold;
            color: #1a202c;
        }
        .clear {
            clear: both;
        }
        .metadata-section {
            margin-bottom: 30px;
            font-size: 12px;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .label {
            width: 22%;
            font-weight: bold;
            color: #000;
        }
        .separator {
            width: 3%;
            text-align: center;
        }
        .value {
            width: 75%;
            color: #2d3748;
        }
        .content-section {
            font-size: 11px;
            text-align: justify;
            margin-bottom: 20px;
        }
        .content-section p {
            margin-bottom: 15px;
            text-indent: 0;
        }
        .footer {
            margin-top: 50px;
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
    <div class="decenio-header">
        "Decenio de la igualdad de oportunidades para mujeres y hombres"<br>
        "Año de la recuperación y consolidación de la economía peruana"
    </div>

    <div class="header">
        <div class="title-container">
            <div class="main-title">CONSTANCIA DE LECTURA DE NOTIFICACION ELECTRONICA</div>
            <div class="doc-number">{{ str_pad($mensaje->id, 7, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <div class="metadata-section">
        <table class="metadata-table">
            <tr>
                <td class="label">Estimado(a)</td>
                <td class="separator">:</td>
                <td class="value" style="font-weight: bold; text-transform: uppercase;">{{ data_get($destinatario, 'usuario_nombre', 'N/A') }}</td>
            </tr>
            <tr>
                <td class="label">Nro. Documento</td>
                <td class="separator">:</td>
                <td class="value" style="font-weight: bold;">{{ data_get($destinatario, 'numero_documento', 'N/A') }}</td>
            </tr>
        </table>
    </div>

    <div class="content-section">
        <p>
            La presente constancia acredita la lectura de la notificación en la Casilla Electrónica por <strong>"{{ $mensaje->asunto }}"</strong> emitida por <strong>{{ data_get($remitente, 'dependencia_nombre', 'N/A') }}</strong>.
        </p>
        <p>
            Le recordamos que la notificación del presente documento se considera efectuada desde el día hábil siguiente de su depósito en la casilla electrónica.
        </p>
    </div>

    <div class="footer">
        <div>CÓDIGO DE VERIFICACIÓN DE TRAZABILIDAD SEGURO (SHA-1)</div>
        <div class="footer-hash">SHA1-LECTURA-{{ $mensaje->id }}-{{ strtotime($mensaje->read_at) }}</div>
    </div>
</body>
</html>
