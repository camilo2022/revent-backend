{{--
    Correo con enlace firmado de acceso a un modulo.
    Props: module (nombre del modulo), url (enlace firmado).
    Estilos 100% inline para compatibilidad con clientes de correo.
--}}
@props(['module', 'url'])
@php
    $logo = config('siigo.mail.logo_url', 'https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250');
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px; width:100%;">

                <!-- Logo -->
                <tr>
                    <td align="center" style="padding:4px 4px 20px 4px;">
                        <img src="{{ $logo }}" alt="REVENT CALZADO S.A.S." width="150" style="display:block; width:150px; max-width:150px; height:auto;">
                    </td>
                </tr>

                <!-- Banner de título -->
                <tr>
                    <td style="background-color:#111827; border-radius:12px; padding:26px 24px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 4px 0; font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; text-transform:uppercase;">
                            REVENT CALZADO S.A.S.
                        </p>
                        <p style="margin:0; font-size:19px; font-weight:bold; color:#ffffff; line-height:1.3;">
                            Acceso a {{ $module }}
                        </p>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Cuerpo -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:24px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Recibimos una solicitud para acceder al módulo de <strong>{{ $module }}</strong>.
                        </p>

                        <p style="margin:0 0 20px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Haz clic en el siguiente botón para ingresar. Este enlace es personal y expira en
                            <strong>24 horas</strong>.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                            <tr>
                                <td align="center" style="border-radius:10px; background-color:#16a34a;">
                                    <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                                        Ingresar a {{ $module }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:20px 0 0 0; font-size:12px; color:#9ca3af; line-height:1.5;">
                            Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                            <span style="word-break:break-all;">{{ $url }}</span>
                        </p>

                        <p style="margin:16px 0 0 0; font-size:12px; color:#9ca3af; line-height:1.5;">
                            Si tú no solicitaste este acceso, puedes ignorar este correo.
                        </p>

                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Pie de página -->
                <tr>
                    <td align="center" style="padding:16px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                        <img src="{{ $logo }}" alt="REVENT CALZADO S.A.S." width="90" style="display:block; width:90px; max-width:90px; height:auto; opacity:0.6; margin:0 auto 6px auto;">
                        <p style="margin:0; font-size:11px; color:#9ca3af;">
                            REVENT CALZADO S.A.S. · {{ config('siigo.mail.domain') }}
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>
