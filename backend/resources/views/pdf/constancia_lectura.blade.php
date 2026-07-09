<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Lectura de Notificación Electrónica</title>
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
        /* 2 líneas en blanco desde decenio → título */
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
        /* 1 enter desde título → número */
        .doc-number {
            font-size: 13pt;
            font-weight: bold;
            color: #000;
            text-align: center;
            margin-top: 11pt;
        }

        /* ── Destinatario ─────────────────────────────────── */
        /* 2–3 enters desde número → Estimado */
        .metadata-section {
            margin-top: 38pt;
            font-size: 11pt;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
        }
        /* espacio sencillo entre filas del bloque */
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
        /* 2 enters desde Nro. Documento → párrafo */
        .content-section {
            margin-top: 38pt;
            font-size: 11pt;
            text-align: justify;
            color: #000;
        }
        /* 2 enters entre párrafos */
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
        /* pegado al margen inferior */
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
        <div class="main-title">CONSTANCIA DE LECTURA DE NOTIFICACION ELECTRONICA</div>
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
