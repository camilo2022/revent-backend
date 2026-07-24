<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 550px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .header { padding: 20px; color: #fff; font-size: 18px; font-weight: bold; }
        .header.success { background: #2e7d32; }
        .header.error { background: #c62828; }
        .content { padding: 25px 20px; color: #333; }
        .content p { font-size: 14px; line-height: 1.6; }
        .filename { background: #f0f0f0; border-radius: 4px; padding: 8px 12px; font-family: 'Courier New', monospace; font-size: 13px; display: inline-block; margin: 10px 0; word-break: break-all; }
        .btn { display: inline-block; background: #2e7d32; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-top: 15px; }
        .error-box { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 6px; padding: 15px; margin-top: 10px; }
        .error-box .label { font-size: 12px; text-transform: uppercase; color: #c62828; font-weight: bold; margin-bottom: 6px; }
        .error-box .message { font-family: 'Courier New', monospace; font-size: 13px; color: #7a1f1a; white-space: pre-wrap; word-break: break-word; }
        .footer { padding: 15px 20px; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $success ? 'success' : 'error' }}">
            {{ $success ? '✔️ Exportación de compras lista' : '❌ Falló la exportación de compras' }}
        </div>

        <div class="content">
            @if ($success)
                <p>Tu exportación de compras Siigo está lista para descargar.</p>

                <div class="filename">{{ $filename }}</div>

                <div>
                    <a href="{{ $downloadUrl }}" class="btn">⬇ Descargar archivo</a>
                </div>

                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                    <a href="{{ $downloadUrl }}" style="color: #2e7d32; word-break: break-all;">{{ $downloadUrl }}</a>
                </p>
            @else
                <p>Ocurrió un error al generar la exportación de compras Siigo.</p>

                <div class="error-box">
                    <div class="label">Mensaje de error</div>
                    <div class="message">{{ $errorMessage ?? 'Error desconocido, revisar logs del servidor.' }}</div>
                </div>
            @endif
        </div>

        <div class="footer">
            Notificación automática generada el {{ now()->format('Y-m-d h:i:s a') }} · Revent Tecnología
        </div>
    </div>
</body>
</html>
