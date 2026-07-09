<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Notificación Electrónica</title>
    <style>
        @page {
            margin-top: 1.0cm;
            margin-bottom: 2.0cm;
            margin-left: 3.0cm;
            margin-right: 3.0cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 11pt;
            line-height: 1.0;
            margin: 0;
            padding: 0;
        }

        /* ── Logo ─────────────────────────────────────────── */
        .logo-header {
            text-align: left;
            margin-bottom: 35pt;
        }
        .logo-header img {
            height: 50pt;
            width: auto;
        }

        /* ── Decenio ──────────────────────────────────────── */
        .decenio-header {
            text-align: center;
            font-size: 8.5pt;
            color: #333;
            line-height: 1.0;
            margin: 0;
        }

        /* ── Título + Número ──────────────────────────────── */
        .titulo-bloque {
            margin-top: 30pt;
            text-align: center;
        }
        .main-title {
            font-size: 13pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            line-height: 1.0;
            margin: 0;
        }
        .doc-number {
            font-size: 13pt;
            font-weight: bold;
            color: #000;
            text-align: center;
            margin-top: 11pt;
        }

        /* ── Destinatario ─────────────────────────────────── */
        .metadata-section {
            margin-top: 38pt;
            font-size: 11pt;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata-table td {
            padding: 0 0 9pt 0;
            vertical-align: top;
            color: #000;
            font-size: 11pt;
            line-height: 1.0;
        }
        .metadata-table tr:last-child td {
            padding-bottom: 0;
        }
        .label {
            width: 25%;
            font-weight: bold;
            color: #000;
        }
        .separator {
            width: 6.5%;
            text-align: left;
            color: #000;
        }
        .value {
            width: 67%;
            color: #000;
        }

        /* ── Cuerpo ───────────────────────────────────────── */
        .content-section {
            margin-top: 38pt;
            font-size: 11pt;
            text-align: justify;
            color: #000;
        }
        .content-section p {
            margin: 0 0 10pt 0;
            line-height: 1.5;
            color: #000;
        }
        .content-section p:last-child {
            margin-bottom: 0;
        }
        .content-section strong {
            color: #000;
        }

        /* ── Pie de página ────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #ccc;
            padding-top: 6pt;
            font-size: 7.5pt;
            color: #555;
            text-align: center;
        }
        .footer-hash {
            font-family: monospace;
            font-size: 8.5pt;
            color: #333;
            margin-top: 3pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

@php
    $logoPath = public_path('img/logo.png');
    $logoSrc  = '';
    if (file_exists($logoPath)) {
        $raw     = file_get_contents($logoPath);
        $logoSrc = 'data:image/png;base64,' . base64_encode($raw);
    }
@endphp

    @if ($logoSrc)
    <div class="logo-header">
        <img src="{{ $logoSrc }}" alt="Gobierno Regional Huánuco" />
    </div>
    @endif

    <div class="decenio-header">
        "{{ env('ANIO_NAME') }}"
    </div>

    <div class="titulo-bloque">
        <div class="main-title">CONSTANCIA DE NOTIFICACION ELECTRONICA</div>
        <div class="doc-number">{{ str_pad($mensaje->id, 7, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="metadata-section">
        <table class="metadata-table">
            <tr>
                <td class="label">Estimado(a)</td>
                <td class="separator">:</td>
                <td class="value" style="text-transform: uppercase;">{{ data_get($destinatario, 'usuario_nombre', 'N/A') }}</td>
            </tr>
            <tr>
                <td class="label">Nro. Documento</td>
                <td class="separator">:</td>
                <td class="value">{{ data_get($destinatario, 'numero_documento', 'N/A') }}</td>
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
