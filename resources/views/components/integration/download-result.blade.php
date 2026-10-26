{{--
    Pantalla "Exportación en proceso" (post de los exportes).
    Props: report (texto del reporte, ej. "facturación"), emails (lista de correos destino).
--}}
@props(['report', 'emails' => []])

<div class="result-wrapper">

    <div class="result-banner">
        <div class="result-banner-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div>
            <div class="result-banner-title">Exportación en proceso</div>
            <div class="result-banner-subtitle">
                Te notificaremos por email cuando el reporte de {{ $report }} esté listo.
            </div>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-title">Se enviará a los siguientes correos</div>

        <div class="email-list">
            @foreach ($emails as $email)
                <div class="email-item">
                    <div class="email-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="email-item-text">{{ $email }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <x-integration.back-link />

</div>
