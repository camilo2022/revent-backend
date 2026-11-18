<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 650px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .header { padding: 20px; color: #fff; font-size: 18px; font-weight: bold; background: #c62828; }
        .content { padding: 20px; color: #333; font-size: 14px; line-height: 1.5; }
        .error-box { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 6px; padding: 15px; font-family: 'Courier New', monospace; font-size: 13px; color: #7a1f1a; white-space: pre-wrap; word-break: break-word; }
        .footer { padding: 15px 20px; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">❌ La sincronización de la bodega WEB con Effi no se pudo completar</div>
    <div class="content">
        <p>Motivo:</p>
        <div class="error-box">{{ $reason }}</div>
        <p>Puede que parte de los cambios sí se hayan hecho en Effi antes del error. Entra a la pantalla de sincronización, consulta el inventario para ver cómo quedó cada código y vuelve a intentarlo: es seguro repetirla, no duplica los ajustes ya hechos.</p>
        <p><a href="{{ $link }}">{{ $link }}</a></p>
    </div>
    <div class="footer">Correo automático de la sincronización Siigo → Effi.</div>
</div>
</body>
</html>
