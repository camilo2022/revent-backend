@extends('layouts.integration')

@section('title', 'Sincronización con Effi en proceso')

@push('styles')
<style>
        * { box-sizing: border-box; }

        .result-wrapper { max-width: 580px; margin: 2rem auto; }

        .result-banner { display: flex; align-items: center; gap: 0.85rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 1.1rem 1.4rem; margin-bottom: 1.5rem; }
        .result-banner.is-error { background: #fef2f2; border-color: #fecaca; }
        .result-banner-icon { width: 40px; height: 40px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #16a34a; border-radius: 50%; }
        .result-banner.is-error .result-banner-icon { background: #dc2626; }
        .result-banner-icon svg { width: 20px; height: 20px; stroke: #fff; }
        .result-banner-title { font-size: 1rem; font-weight: 600; color: #14532d; }
        .result-banner.is-error .result-banner-title { color: #7f1d1d; }
        .result-banner-subtitle { font-size: 0.82rem; color: #166534; }
        .result-banner.is-error .result-banner-subtitle { color: #991b1b; }

        .info-card { background: #ffffff; border: 1px solid #eef0f2; border-radius: 16px; padding: 1.4rem 1.5rem; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04); margin-bottom: 1rem; }
        .info-card-title { font-size: 0.78rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.9rem; }
        .info-step { display: flex; align-items: flex-start; gap: 0.7rem; margin-bottom: 0.9rem; }
        .info-step:last-child { margin-bottom: 0; }
        .info-step-icon { width: 28px; height: 28px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #eff6ff; border-radius: 8px; margin-top: 0.05rem; }
        .info-step-icon svg { width: 14px; height: 14px; stroke: #1d4ed8; }
        .info-step-text { font-size: 0.85rem; color: #374151; line-height: 1.45; }
        .info-step-text strong { color: #1f2937; }
        .email-highlight { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: #1d4ed8; background: #eff6ff; padding: 0.35rem 0.7rem; border-radius: 8px; margin-top: 0.4rem; word-break: break-all; }

        .progress-phase { font-size: 0.9rem; font-weight: 600; color: #1f2937; }
        .progress-counters { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.7rem; }
        .progress-counter { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0.35rem 0.7rem; font-size: 0.78rem; color: #4b5563; }
        .progress-counter strong { color: #111827; font-variant-numeric: tabular-nums; }
        .progress-review { margin-top: 1rem; font-size: 0.8rem; color: #374151; }
        .progress-review table { width: 100%; border-collapse: collapse; margin-top: .4rem; }
        .progress-review th, .progress-review td { text-align: left; padding: .35rem .5rem; border-top: 1px solid #f1f3f5; vertical-align: top; }

        .back-link { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: #4f46e5; text-decoration: none; margin-top: 1.4rem; margin-right: 1.4rem; }
        .back-link:hover { text-decoration: underline; }
        .back-link svg { width: 15px; height: 15px; }
</style>
@endpush

@section('content')
<div class="result-wrapper">

    <div class="result-banner" id="banner">
        <div class="result-banner-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
            <div class="result-banner-title" id="bannerTitle">Sincronización con Effi en proceso</div>
            <div class="result-banner-subtitle" id="bannerSubtitle">La sincronización de la bodega WEB se está ejecutando en segundo plano.</div>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-title">Avance</div>
        <div class="progress-phase" id="phase">Consultando…</div>
        <div class="progress-counters" id="counters"></div>
        <div class="progress-review" id="review"></div>
    </div>

    <div class="info-card">
        <div class="info-card-title">¿Qué sigue?</div>

        <div class="info-step">
            <div class="info-step-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="info-step-text">
                Estamos cambiando referencias, creando artículos y ajustando las unidades de la bodega WEB en Effi para que quede igual que Siigo. Este proceso puede tardar varios minutos. Puedes cerrar esta página: mientras corre no se puede lanzar otra sincronización.
            </div>
        </div>

        <div class="info-step">
            <div class="info-step-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="info-step-text">
                <strong>Si todo sale bien</strong>, recibirás un correo con el detalle: unidades que ingresaron y salieron, referencias cambiadas, artículos creados y lo que haya quedado pendiente.
            </div>
        </div>

        <div class="info-step">
            <div class="info-step-icon" style="background:#fef2f2;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="info-step-text">
                <strong>Si algo falla</strong> (Effi rechaza un cambio, hay otra sincronización en curso, etc.), también te lo notificaremos por correo con el motivo de cada código. Es seguro repetir la sincronización: no duplica los ajustes ya hechos.
            </div>
        </div>

        <div class="info-step">
            <div class="info-step-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8l10 6 10-6"/></svg>
            </div>
            <div class="info-step-text">
                Serás notificado en:
                <div class="email-highlight">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    {{ $email }}
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('siigo.effi_sync') }}" class="back-link" id="backSync" style="display:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a la sincronización
    </a>
    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const LABELS = { referencias: 'Referencias cambiadas', creados: 'Artículos creados', vaciados: 'Vaciados', controles: 'Controles cambiados', ingresos: 'Ingresos', egresos: 'Egresos', anulados: 'Anulados' };

    async function poll() {
        let s = {};
        try {
            s = (await (await fetch(@json(route('siigo.effi_sync_status')), { headers: { 'Accept': 'application/json' } })).json()).data || {};
        } catch (e) { setTimeout(poll, 5000); return; }

        const running = ['planeando', 'ejecutando'].includes(s.estado);
        document.getElementById('phase').textContent = s.fase || s.mensaje || (s.estado === 'terminado' ? 'Terminó la sincronización.' : '');
        document.getElementById('counters').innerHTML = Object.entries(s.hechos || {}).map(([k, v]) =>
            `<div class="progress-counter">${esc(LABELS[k] || k)}: <strong>${esc(v)}</strong></div>`).join('');

        const revision = s.revision || [];
        document.getElementById('review').innerHTML = revision.length
            ? `<strong>Para revisar después de sincronizar (${revision.length})</strong><table><thead><tr><th>Código</th><th>Por qué</th></tr></thead><tbody>${revision.map(r =>
                `<tr><td>${esc(r.codigo)}</td><td>${r.motivos.map(m => `<div>${esc(m)}</div>`).join('')}</td></tr>`).join('')}</tbody></table>` : '';

        if (!running) {
            const banner = document.getElementById('banner');
            banner.classList.toggle('is-error', s.estado === 'error');
            document.getElementById('bannerTitle').textContent = s.estado === 'error' ? 'La sincronización no se pudo completar' : 'Sincronización con Effi terminada';
            document.getElementById('bannerSubtitle').textContent = s.estado === 'error' ? 'Te avisamos por correo con el motivo.' : 'El detalle completo llegó por correo.';
            document.getElementById('backSync').style.display = 'inline-flex';
            return;
        }
        setTimeout(poll, 4000);
    }

    poll();
})();
</script>
@endpush
