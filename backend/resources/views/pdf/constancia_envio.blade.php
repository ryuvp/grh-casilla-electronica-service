<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Notificación Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1a202c;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
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
    <div class="header">
        <div class="title-container">
            <div class="main-title">CONSTANCIA DE NOTIFICACION ELECTRONICA</div>
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
            La presente constancia acredita el depósito de la notificación del <strong>"{{ $mensaje->asunto }}"</strong> en la Casilla Electrónica por Notificación Electrónica del Gobierno Regional de Huánuco emitida por <strong>{{ data_get($remitente, 'dependencia_nombre', 'N/A') }}</strong>.
        </p>
        <p>
            Le recordamos que para efectos de acreditar que la notificación se ha realizado válidamente, el Sistema de Casilla Electrónica (SCE) genera automáticamente la constancia de notificación electrónica, en la que consta la fecha y hora exacta del depósito del documento, las mismas que se realizarán en el horario establecido por las leyes vigentes, caso contrario de encontrarse fuera de dicho horario se tomará como fecha valida de notificación el día hábil siguiente.
        </p>
        <p>
            Asimismo, para efectos del cómputo de plazos se informa que éste iniciará desde el día siguiente de efectuada la confirmación de la recepción mediante su acuse de recibo que se genera cuando es leída la presente notificación o desde el día hábil siguiente de transcurrido los cinco (05) primeros días hábiles consecutivos a la presente notificación.
        </p>
        <p>
            Finalmente, de conformidad con el numeral 5.6 de la Ley N° 31736, Ley que regula la notificación administrativa mediante casilla electrónica se deja constancia que el SCE ha remitido una comunicación al correo electrónico que a la fecha se encuentra registrado: <strong>{{ data_get($destinatario, 'email', 'N/A') }}</strong>, y, vía mensaje de texto, al teléfono celular: <strong>{{ data_get($destinatario, 'telefono', 'N/A') }}</strong>, los mismos que usted declaró.
        </p>
    </div>

    <div class="footer">
        <div>CÓDIGO DE VERIFICACIÓN DE TRAZABILIDAD SEGURO (SHA-1)</div>
        <div class="footer-hash">SHA1-ENVIO-{{ $mensaje->id }}-{{ strtotime($mensaje->created_at) }}</div>
    </div>
</body>
</html>
