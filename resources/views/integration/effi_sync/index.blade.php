@extends('layouts.integration')

@section('title', 'Sincronizar Effi con Siigo')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/effi-sync.css') }}">
@endpush

@section('content')
<div class="ef-wrap">

<div id="mainArea">
    <div class="ef-card">
        <div class="excel-upload-title">Sincronizar Effi con Siigo</div>
        <div class="excel-upload-subtitle">
            Compara, código por código, el inventario de la bodega <strong>WEB</strong> de Siigo (bodega {{ $siigoWarehouse }}) con la bodega de Effi.
            La sincronización dejará Effi exactamente igual a Siigo con un ajuste de inventario por la diferencia.
        </div>

        <div class="excel-btn-row">
            <button type="button" class="excel-submit-btn" id="btnCompare">
                <span class="btn-spinner"></span>
                <span class="btn-label">Consultar inventario y diferencias</span>
            </button>
            <button type="button" class="excel-submit-btn excel-submit-btn--ghost" id="btnPlan">
                <span class="btn-spinner"></span>
                <span class="btn-label">Ver plan de sincronización</span>
            </button>
        </div>

        <div class="ef-note">
            «Consultar» y «Ver plan» solo leen: no escriben nada en Effi. Cada consulta puede tardar unos 30 segundos.
            @if (!$syncEnabled)
                La sincronización que escribe en Effi está <strong>desactivada</strong> (EFFI_SYNC_ENABLED) hasta que se apruebe el plan.
            @endif
        </div>

        <div class="excel-error" id="errorBox"></div>
    </div>

    <div class="ef-card ef-hidden" id="planCard">
        <div class="excel-upload-title">Plan de sincronización</div>
        <div class="ef-meta" id="planMeta"></div>
        <div class="ef-tiles" id="planTiles"></div>

        <div class="ef-main-tabs" id="mainTabs"></div>

        <div id="panelDec" class="ef-hidden">
            <div class="ef-note" style="margin:.3rem 0 .8rem">
                El sistema no decide ni interpreta nada: en estos casos elige tú qué hacer. La sincronización no se puede ejecutar mientras haya decisiones sin responder.
                <strong id="pendingCount"></strong>
                <button type="button" class="ef-tab" id="applyRecommended" style="margin-left:.4rem">Aplicar la recomendación donde la hay</button>
            </div>
            <div id="decisions"></div>
            <div class="ef-empty ef-hidden" id="noDecisions">No hay decisiones que tomar: todo se puede sincronizar tal como está.</div>
        </div>

        <div id="panelOk">
            <div class="ef-note" style="margin:.3rem 0 .8rem">Todo lo de esta pestaña se sincroniza sin que tengas que decidir nada.</div>
            <div class="ef-toolbar">
                <div class="ef-tabs" id="planTabs"></div>
            </div>
            <div class="ef-table-wrap">
                <table class="ef-table">
                    <thead><tr><th>Código Siigo</th><th>Descripción Siigo</th><th class="num">Siigo</th><th class="num">Effi</th><th>Qué se haría en Effi</th><th></th></tr></thead>
                    <tbody id="planRows"></tbody>
                </table>
            </div>
            <div class="ef-count" id="planCount"></div>
        </div>

        @if ($syncEnabled)
        <div class="ef-note" style="margin-top:1rem">
            Revisa el plan. Para ejecutarlo escribe <strong>SINCRONIZAR</strong> y pulsa el botón: esto <strong>escribe en Effi</strong> (referencias, artículos nuevos y ajustes de inventario).
            <div style="display:flex;gap:.6rem;margin-top:.6rem;flex-wrap:wrap">
                <input type="text" class="excel-field-input" id="confirmText" placeholder="SINCRONIZAR" style="max-width:220px">
                <button type="button" class="excel-submit-btn" id="btnSync" disabled>Sincronizar con Effi</button>
            </div>
            <div id="syncStatus" style="margin-top:.6rem"></div>
        </div>
        @endif
    </div>

    <div class="ef-card ef-hidden" id="resultCard">
        <div class="ef-meta" id="meta"></div>

        <div class="ef-warn ef-hidden" id="stockWarning">
            Effi no está entregando el stock de los artículos: el usuario de la API key necesita permiso de consulta sobre
            <strong>«Artículos - Stock disponible» (función 156)</strong>. Mientras tanto se ve el inventario de Siigo y el cruce de códigos con Effi, sin las unidades de Effi.
        </div>

        <div class="ef-tiles" id="tiles"></div>

        <div class="ef-toolbar">
            <div class="ef-tabs" id="tabs"></div>
            <input type="search" class="excel-field-input ef-search" id="search" placeholder="Buscar código o descripción">
        </div>

        <div class="ef-table-wrap">
            <table class="ef-table">
                <thead>
                    <tr>
                        <th>Código Siigo</th>
                        <th>Descripción Siigo</th>
                        <th class="num">Siigo</th>
                        <th class="num">Effi</th>
                        <th class="num">Diferencia</th>
                        <th>Artículo en Effi</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="ef-count" id="rowsCount"></div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const STATUS = {
        igual: 'Igual',
        diferencia: 'Diferencia',
        no_existe: 'No existe en Effi',
        sin_control: 'Effi no controla stock por bodega',
        sin_stock_effi: 'Sin stock de Effi',
        repetida: 'Referencia repetida en Effi',
    };
    const btn = document.getElementById('btnCompare');
    const label = btn.querySelector('.btn-label');
    const errorBox = document.getElementById('errorBox');
    const fmt = n => n === null || n === undefined ? '—' : Number(n).toLocaleString('es-CO');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let data = null, filter = 'todos';

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.classList.add('is-loading');
        label.textContent = 'Consultando Siigo y Effi…';
        errorBox.classList.remove('show');

        try {
            const res = await fetch(@json(route('siigo.effi_sync_compare')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.message || 'Error desconocido');
            data = json.data;
            render();
        } catch (e) {
            errorBox.textContent = e.message;
            errorBox.classList.add('show');
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            label.textContent = 'Consultar inventario y diferencias';
        }
    });

    document.getElementById('search').addEventListener('input', renderRows);

    // ---- Plan de sincronización (solo consulta) ----
    const STEP = {
        referencia: s => `Cambiar referencia <strong>${esc(s.de)}</strong> → <strong>${esc(s.a)}</strong>`,
        descripcion: s => `Cambiar nombre «${esc(s.de)}» → <strong>«${esc(s.a)}»</strong>`,
        crear: s => `Crear artículo «${esc(s.datos.descripcion)}»${s.datos.categoria ? ` (categoría ${esc(s.datos.categoria)})` : ' <em>(sin categoría)</em>'}${(s.datos.precios || []).length ? '' : ' <em>(sin precios)</em>'}`,
        vaciar: s => `Vaciar ${fmt(s.cantidad)} u. (sin control por bodega)`,
        control: () => 'Pasar a control por bodega',
        ajuste: s => s.cantidad > 0 ? `Ingreso de <span class="ef-pos">+${fmt(s.cantidad)}</span> u.` : `Egreso de <span class="ef-neg">${fmt(s.cantidad)}</span> u.`,
    };
    const STEP_TABS = [['todos', 'Todos'], ['referencia', 'Cambiar referencia'], ['descripcion', 'Cambiar nombre'], ['crear', 'Crear artículo'], ['control', 'Cambiar control'], ['ajuste', 'Ajustes'], ['avisos', 'Con avisos']];
    const planBtn = document.getElementById('btnPlan');
    let plan = null, planFilter = 'todos';
    let choices = { modelos: {}, duplicados: {} };
    let mainTab = 'ok';

    // ---- Decisiones: categorías que no existen en Effi y referencias duplicadas ----
    const CONTROL = { 0: 'sin trazabilidad', 1: 'SIN control de stock', 2: 'control por empresa', 3: 'control por bodega' };

    function pendingDecisions() {
        const d = plan ? plan.decisiones : { categorias: [], duplicados: [] };
        return d.categorias.filter(c => !choices.modelos[c.modelo]).length + d.duplicados.filter(c => !choices.duplicados[c.codigo]).length;
    }

    const okItems = () => plan.items.filter(i => !i.categoria_pendiente);
    const totalDecisions = () => plan.decisiones.categorias.length + plan.decisiones.duplicados.length;

    // Pestañas principales: lo que se sincroniza solo / lo que tiene que decidir la persona
    function renderMainTabs() {
        if (!plan) return;
        const pending = pendingDecisions();
        const tabs = [
            ['ok', `Listo para sincronizar (${fmt(okItems().length)})`, ''],
            ['dec', `Decisiones que te tocan (${totalDecisions()})`, pending ? `<span class="ef-badge-pending">${pending} sin responder</span>` : '<span class="ef-badge-done">todas respondidas</span>'],
        ];
        document.getElementById('mainTabs').innerHTML = tabs.map(([k, l, b]) =>
            `<button type="button" class="ef-main-tab ${k === mainTab ? 'active' : ''}" data-k="${k}">${l} ${b}</button>`).join('');
        document.querySelectorAll('#mainTabs .ef-main-tab').forEach(t => t.addEventListener('click', () => { mainTab = t.dataset.k; renderMainTabs(); }));
        document.getElementById('panelOk').classList.toggle('ef-hidden', mainTab !== 'ok');
        document.getElementById('panelDec').classList.toggle('ef-hidden', mainTab !== 'dec');
        document.getElementById('noDecisions').classList.toggle('ef-hidden', totalDecisions() > 0);
    }

    function updateSyncButton() {
        const pending = pendingDecisions();
        const counter = document.getElementById('pendingCount');
        if (counter) counter.textContent = pending ? `Pendientes: ${pending}` : 'Todas respondidas.';
        renderMainTabs();
        const sync = document.getElementById('btnSync');
        const confirm = document.getElementById('confirmText');
        if (sync && confirm) sync.disabled = !plan || pending > 0 || confirm.value.trim() !== 'SINCRONIZAR';
    }

    function renderDecisions() {
        const d = plan.decisiones;
        const catOptions = Object.entries(plan.categorias).map(([id, n]) => `<option value="${id}">${esc(n)}</option>`).join('');

        const categorias = d.categorias.map((c, i) => {
            const cur = choices.modelos[c.modelo] || {};
            const name = c.modelo === '' ? '(sin modelo en Siigo)' : `«${esc(c.modelo)}»`;
            const radio = (v, text) => `<label class="ef-opt"><input type="radio" name="m${i}" value="${v}" ${cur.accion === v ? 'checked' : ''}> ${text}</label>`;
            return `<div class="ef-decision" data-modelo="${esc(c.modelo)}" data-i="${i}">
                <div class="ef-decision-title">Categoría para ${c.cantidad} artículo(s) nuevo(s) con modelo ${name}</div>
                <div class="ef-detail">Ejemplos: ${c.ejemplos.map(esc).join(', ')}. En Siigo tienen el modelo ${name} y en Effi no hay ninguna categoría con ese nombre. ¿Qué categoría llevan en Effi?</div>
                ${radio('existente', `Usar una categoría que ya existe: <select class="ef-select" data-sel="${i}">${catOptions}</select>${c.sugerida ? ` <span class="ef-reco">Recomendada: ${esc(c.sugerida.nombre)}</span> <span class="ef-detail">${esc(c.sugerida.motivo)}</span>` : ''}`)}
                ${c.modelo === '' ? '' : radio('crear', `Crear la categoría «${esc(c.modelo)}» en Effi <span class="ef-detail">(solo se crea si no existe ya; nunca se duplica)</span>`)}
                ${radio('ninguna', 'Crear los artículos sin categoría <span class="ef-detail">(la asignas después en Effi)</span>')}
            </div>`;
        }).join('');

        const duplicados = d.duplicados.map((c, i) => {
            const cur = choices.duplicados[c.codigo] || {};
            const art = a => `#${esc(a.id)} · ${CONTROL[a.gestion_stock] || a.gestion_stock} · ${fmt(a.stock)} u. en la bodega`;
            const radio = (v, checked, text) => `<label class="ef-opt"><input type="radio" name="d${i}" value="${v}" ${checked ? 'checked' : ''}> ${text}</label>`;
            return `<div class="ef-decision" data-codigo="${esc(c.codigo)}" data-i="${i}">
                <div class="ef-decision-title">${esc(c.codigo)} está dos veces en Effi · Siigo tiene ${fmt(c.siigo)} u.</div>
                <div class="ef-detail">${esc(c.descripcion)}. ¿Cuál se inhabilita? El inhabilitado se vacía y se anula (en Effi se puede reactivar); el otro queda con las unidades de Siigo.</div>
                ${c.articulos.map(a => radio(a.id, cur.inhabilitar === a.id, `Inhabilitar ${art(a)}${c.recomendada === a.id ? ' <span class="ef-reco">Recomendado: es la que no controla stock por bodega</span>' : ''}`)).join('')}
                ${radio('ninguno', cur.ninguno, 'No tocar ninguna por ahora')}
            </div>`;
        }).join('');

        document.getElementById('decisions').innerHTML = categorias + duplicados;

        document.querySelectorAll('#decisions [data-modelo]').forEach(card => {
            const i = card.dataset.i, c = d.categorias[i], sel = card.querySelector('select');
            sel.value = (choices.modelos[c.modelo] && choices.modelos[c.modelo].categoria) || (c.sugerida ? c.sugerida.id : sel.options[0]?.value);
            const save = () => {
                const v = card.querySelector('input[type=radio]:checked');
                if (v) choices.modelos[c.modelo] = { accion: v.value, categoria: v.value === 'existente' ? sel.value : undefined };
                updateSyncButton();
            };
            card.querySelectorAll('input[type=radio]').forEach(r => r.addEventListener('change', save));
            sel.addEventListener('change', () => { card.querySelector('input[value=existente]').checked = true; save(); });
        });
        document.querySelectorAll('#decisions [data-codigo]').forEach(card => {
            const c = d.duplicados[card.dataset.i];
            card.querySelectorAll('input[type=radio]').forEach(r => r.addEventListener('change', () => {
                choices.duplicados[c.codigo] = r.value === 'ninguno' ? { ninguno: true } : { inhabilitar: r.value };
                updateSyncButton();
            }));
        });
        updateSyncButton();
    }

    document.getElementById('applyRecommended').addEventListener('click', () => {
        if (!plan) return;
        plan.decisiones.categorias.forEach(c => {
            // Solo las que tienen recomendación; las demás las eliges tú
            if (!choices.modelos[c.modelo] && c.sugerida) choices.modelos[c.modelo] = { accion: 'existente', categoria: String(c.sugerida.id) };
        });
        plan.decisiones.duplicados.forEach(c => {
            if (!choices.duplicados[c.codigo] && c.recomendada) choices.duplicados[c.codigo] = { inhabilitar: c.recomendada };
        });
        renderDecisions();
    });

    planBtn.addEventListener('click', async () => {
        planBtn.disabled = true;
        planBtn.classList.add('is-loading');
        planBtn.querySelector('.btn-label').textContent = 'Armando el plan…';
        errorBox.classList.remove('show');
        try {
            const res = await fetch(@json(route('siigo.effi_sync_plan')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.message || 'Error desconocido');
            if (!json.data.ok) throw new Error(json.data.message);
            plan = json.data;
            choices = { modelos: {}, duplicados: {} };
            mainTab = (plan.decisiones.categorias.length + plan.decisiones.duplicados.length) ? 'dec' : 'ok';
            renderDecisions();
            renderPlan();
        } catch (e) {
            errorBox.textContent = e.message;
            errorBox.classList.add('show');
        } finally {
            planBtn.disabled = false;
            planBtn.classList.remove('is-loading');
            planBtn.querySelector('.btn-label').textContent = 'Ver plan de sincronización';
        }
    });

    function renderPlan() {
        const r = plan.resumen;
        document.getElementById('planCard').classList.remove('ef-hidden');
        document.getElementById('planMeta').innerHTML = esc(`Plan del ${plan.fecha} · Siigo: bodega WEB (${plan.bodega_siigo}) · Effi: ${plan.bodega_effi}`) + (plan.notas || []).map(n => `<div>ℹ ${esc(n)}</div>`).join('');
        const tiles = [
            ['Códigos con cambios', fmt(r.codigos_con_cambios), ''],
            ['Referencias a cambiar', fmt(r.referencias_a_cambiar), 'quedan con el código de Siigo'],
            ['Nombres a cambiar', fmt(r.descripciones_a_cambiar), 'quedan con la descripción de Siigo'],
            ['Artículos a crear', fmt(r.articulos_a_crear), 'no existen en Effi'],
            ['Control a cambiar', fmt(r.controles_a_cambiar), `${fmt(r.articulos_a_vaciar)} se vacían antes`],
            ['Ingresos', fmt(r.unidades_ingreso), `${fmt(r.ingresos)} movimientos`],
            ['Egresos', fmt(r.unidades_egreso), `${fmt(r.egresos)} movimientos`],
            ['Con avisos', fmt(r.con_avisos), 'se sincronizan; revisar después'],
            ['No se tocan', fmt(r.manuales), 'requieren decisión tuya'],
        ];
        document.getElementById('planTiles').innerHTML = tiles.map(([l, v, h]) =>
            `<div class="ef-tile"><div class="ef-tile-label">${l}</div><div class="ef-tile-value">${v}</div><div class="ef-tile-hint">${h}</div></div>`).join('');
        document.getElementById('planTabs').innerHTML = STEP_TABS.map(([k, l]) =>
            `<button type="button" class="ef-tab ${k === planFilter ? 'active' : ''}" data-k="${k}">${l}</button>`).join('');
        document.querySelectorAll('#planTabs .ef-tab').forEach(t => t.addEventListener('click', () => { planFilter = t.dataset.k; renderPlan(); }));

        // Solo lo que se sincroniza sin decidir nada (los artículos con categoría pendiente van en «Decisiones»)
        const items = okItems().filter(i => planFilter === 'todos' || (planFilter === 'avisos' ? i.avisos.length : i.pasos.some(p => p.tipo === planFilter)));
        const shown = items.slice(0, 1000);
        document.getElementById('planRows').innerHTML = shown.length ? shown.map(i => i.motivo !== undefined
            ? ''
            : `<tr><td>${esc(i.codigo)}</td><td>${esc(i.descripcion)}</td><td class="num">${fmt(i.siigo)}</td><td class="num">${fmt(i.effi)}</td>
                <td>${i.pasos.map(p => `<div>${STEP[p.tipo](p)}</div>`).join('')}${(i.avisos || []).map(a => `<div class="ef-detail">⚠ ${esc(a)}</div>`).join('')}</td>
                <td>${SYNC_ENABLED ? `<button type="button" class="ef-tab" data-solo="${esc(i.codigo)}">Probar solo este</button>` : ''}</td></tr>`
        ).join('') : '<tr><td colspan="6" class="ef-empty">Sin filas para este filtro.</td></tr>';
        document.querySelectorAll('#planRows [data-solo]').forEach(b => b.addEventListener('click', () => testOne(b.dataset.solo)));
        document.getElementById('planCount').textContent = items.length > shown.length ? `Mostrando ${shown.length} de ${items.length}.` : `${items.length} filas.`;
    }

    const SYNC_ENABLED = @json($syncEnabled);

    // Prueba con un solo código: se sincroniza únicamente ese artículo, sin esperar las decisiones del resto del plan
    async function testOne(code) {
        if (!confirm(`Se sincronizará SOLO el código ${code} en Effi (real). ¿Continuar?`)) return;
        const res = await fetch(@json(route('siigo.effi_sync_sync')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ confirmacion: 'SINCRONIZAR', solo: code, decisiones: choices }),
        });
        const json = await res.json();
        if (!json.ok) { errorBox.textContent = json.message; errorBox.classList.add('show'); return; }
        showRunning(json.correos || []);
    }

    // Al lanzar la sincronización se pasa a la pantalla «en proceso» (como en las cargas de traslados y órdenes de compra)
    function showRunning() {
        location.href = @json(route('siigo.effi_sync_running'));
    }
    const confirmText = document.getElementById('confirmText');
    if (confirmText) {
        const syncBtn = document.getElementById('btnSync');
        confirmText.addEventListener('input', updateSyncButton);
        syncBtn.addEventListener('click', async () => {
            const box = document.getElementById('syncStatus');
            syncBtn.disabled = true;
            const res = await fetch(@json(route('siigo.effi_sync_sync')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ confirmacion: confirmText.value.trim(), decisiones: choices }),
            });
            const json = await res.json();
            if (!json.ok) { box.textContent = json.message; return; }
            showRunning(json.correos || []);
        });
    }

    function render() {
        const r = data.resumen;
        const stock = data.stock_effi_disponible;

        document.getElementById('resultCard').classList.remove('ef-hidden');
        document.getElementById('meta').textContent =
            `Consulta del ${data.fecha} · Siigo: bodega WEB (${data.bodega_siigo}) · Effi: ${data.bodega_effi}`;
        document.getElementById('stockWarning').classList.toggle('ef-hidden', stock);

        const tiles = [
            ['Unidades en Siigo', fmt(r.unidades_siigo), `${fmt(r.codigos_siigo)} códigos con saldo`],
            ['Unidades en Effi', stock ? fmt(r.unidades_effi) : 'Sin permiso', 'misma bodega'],
            ['Códigos iguales', stock ? fmt(r.iguales) : '—', ''],
            ['Con diferencia', stock ? fmt(r.con_diferencia) : '—', ''],
            ['No existen en Effi', fmt(r.no_existen), `${fmt(r.unidades_no_existen)} unidades de Siigo`],
            ['Sin control por bodega', fmt(r.sin_control), 'no se pueden ajustar así'],
        ];
        document.getElementById('tiles').innerHTML = tiles.map(([l, v, h]) =>
            `<div class="ef-tile"><div class="ef-tile-label">${l}</div><div class="ef-tile-value">${v}</div><div class="ef-tile-hint">${h}</div></div>`).join('');

        const counts = data.filas.reduce((acc, f) => (acc[f.estado] = (acc[f.estado] || 0) + 1, acc), {});
        const tabs = [['todos', `Todos (${data.filas.length})`], ...Object.keys(STATUS).filter(k => counts[k]).map(k => [k, `${STATUS[k]} (${counts[k]})`])];
        if (!tabs.some(([k]) => k === filter)) filter = 'todos';
        document.getElementById('tabs').innerHTML = tabs.map(([k, l]) =>
            `<button type="button" class="ef-tab ${k === filter ? 'active' : ''}" data-k="${k}">${l}</button>`).join('');
        document.querySelectorAll('.ef-tab').forEach(t => t.addEventListener('click', () => { filter = t.dataset.k; render(); }));

        renderRows();
    }

    function renderRows() {
        const q = document.getElementById('search').value.trim().toUpperCase();
        const rows = data.filas.filter(f => (filter === 'todos' || f.estado === filter)
            && (!q || f.codigo.includes(q) || String(f.descripcion).toUpperCase().includes(q) || String(f.effi_referencia || '').toUpperCase().includes(q)));
        const shown = rows.slice(0, 1500);

        document.getElementById('rows').innerHTML = shown.length ? shown.map(f => {
            const diff = f.diferencia === null ? '—'
                : `<span class="${f.diferencia > 0 ? 'ef-pos' : (f.diferencia < 0 ? 'ef-neg' : '')}">${f.diferencia > 0 ? '+' : ''}${fmt(f.diferencia)}</span>`;
            const art = f.effi_id
                ? `${esc(f.effi_referencia)}<div class="ef-detail">#${esc(f.effi_id)}${f.cruce === 'descripcion' ? ' · por descripción' : ''} · ${esc(f.effi_descripcion)}</div>`
                : '<span class="ef-detail">—</span>';
            return `<tr>
                <td>${esc(f.codigo)}</td>
                <td>${esc(f.descripcion)}</td>
                <td class="num">${fmt(f.siigo)}</td>
                <td class="num">${fmt(f.effi)}</td>
                <td class="num">${diff}</td>
                <td>${art}</td>
                <td><span class="ef-badge ${f.estado}">${STATUS[f.estado] || f.estado}</span></td>
            </tr>`;
        }).join('') : '<tr><td colspan="7" class="ef-empty">Sin filas para este filtro.</td></tr>';

        document.getElementById('rowsCount').textContent = rows.length > shown.length
            ? `Mostrando ${shown.length} de ${rows.length} filas; usa el buscador para acotar.` : `${rows.length} filas.`;
    }
})();
</script>
@endpush
