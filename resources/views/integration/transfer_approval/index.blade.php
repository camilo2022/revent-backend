@extends('layouts.integration')

@section('title', 'Traslados por recepcionar')

@push('styles')
    @include('integration.transfer_approval.styles')
@endpush

@section('content')
<div class="excel-upload-wrapper">

    <!-- =========================================================
         BUSCADOR
    ========================================================== -->

    <div class="excel-upload-card">
        <div class="excel-upload-title">
            Traslados por recepcionar
        </div>

        <div class="excel-upload-subtitle">
            Escribe el numero de la nota de traslado (por ejemplo <strong>NT-12345</strong>) que llego a la bodega de transito
            para ver lo que trae, lo disponible y aprobar las unidades que recibiste.
        </div>

        <form id="searchForm" autocomplete="off">
            <div class="excel-field-group">
                <label for="token" class="excel-field-label">
                    Token *
                </label>

                <div class="excel-search-row">
                    <input type="text" name="token" id="token" class="excel-field-input" required autofocus>
                </div>

                <div class="excel-field-hint">
                    Este token se reutiliza tambien al confirmar la aprobacion, asi que debe permanecer vigente durante todo el proceso.
                </div>
            </div>

            <div class="excel-field-group">
                <label for="query" class="excel-field-label">
                    N° de nota de traslado *
                </label>

                <div class="excel-search-row">
                    <input type="text" name="query" id="query" class="excel-field-input" placeholder="Ej: NT-12345" required>
                    <button type="submit" class="excel-submit-btn" id="submitBtn"><span class="btn-spinner"></span><span class="btn-label">Buscar</span></button>
                </div>

                <div class="excel-field-hint">
                    Escribe el numero exacto tal como aparece en Siigo.
                </div>
            </div>
        </form>
    </div>

    <!-- =========================================================
         RESULTADO
    ========================================================== -->

    <div class="result-card" id="resultCard">

        <div class="result-header">
            <div>
                <div class="result-doc-name" id="resDocName">-</div>
                <div class="result-doc-info">
                    <span>Fecha NT: <strong id="resDocDate">-</strong></span>
                    <span>Sale de: <strong id="resOrigin">-</strong></span>
                </div>
                <div class="result-doc-info">
                    <span>Bodega de transito: <strong id="resTransit">-</strong></span>
                    <span>Llega a: <strong id="resDestination">-</strong></span>
                </div>
            </div>

            <span class="badge badge-ok" id="resBadge">En transito</span>
        </div>

        <!-- RESUMEN -->

        <div class="summary-grid">
            <div class="summary-card summary-requested">
                <div class="amount" id="resRequested">0</div>
                <div class="label">Cantidad en la nota</div>
            </div>

            <div class="summary-card summary-pending">
                <div class="amount" id="resAvailable">0</div>
                <div class="label">Maximo a aprobar</div>
            </div>

            <div class="summary-card summary-received">
                <div class="amount" id="resApproving">0</div>
                <div class="label">Cantidad aprobando</div>
            </div>
        </div>

        <!-- ITEMS -->

        <div class="section-title">
            Items de la nota
        </div>

        <div class="items-scroll">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th>Referencia</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>En la nota</th>
                        <th>Disponible</th>
                        <th>Aprobar</th>
                    </tr>
                </thead>

                <tbody id="resItemsBody"></tbody>
            </table>
        </div>

        <div class="section-title">
            Imagenes por referencia
        </div>

        <div class="image-gallery" id="resImages"></div>

        <div class="approval-confirm">
            <button type="button" class="checklist-submit-btn" id="confirmBtn">
                Confirmar aprobacion
            </button>

            <button type="button" class="btn-reset" id="btnReset">
                Buscar otra nota
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {

    const SEARCH_URL = "{{ route('siigo.transfer_approval_search') }}";
    const CONFIRM_URL = "{{ route('siigo.transfer_approval_confirm') }}";
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const form = document.getElementById('searchForm');
    const tokenInput = document.getElementById('token');
    const queryInput = document.getElementById('query');
    const submitBtn = document.getElementById('submitBtn');
    const btnLabel = submitBtn.querySelector('.btn-label');
    const confirmBtn = document.getElementById('confirmBtn');
    const resultCard = document.getElementById('resultCard');
    const itemsBody = document.getElementById('resItemsBody');

    let currentNote = null;

    function esc(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatThousands(value) {
        return String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function dateFmt(value) {
        const match = String(value || '').match(/^(\d{4})(\d{2})(\d{2})$/);

        return match ? match[3] + '/' + match[2] + '/' + match[1] : (value || '-');
    }

    async function post(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json().catch(function () { return {}; });

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Ocurrio un error inesperado. Intenta de nuevo.');
        }

        return data;
    }

    function quantityInputs() {
        return Array.from(itemsBody.querySelectorAll('.qty-input'));
    }

    /** Semaforo: menos de lo permitido = rojo, igual = verde, de mas = azul (con aviso del maximo). */
    function paint(input) {
        if (input.disabled) {
            return;
        }

        const max = Number(input.max) || 0;
        const value = parseInt(input.value, 10) || 0;
        const note = input.closest('td').querySelector('.qty-note');

        input.classList.remove('partial', 'complete', 'over');
        input.classList.add(value > max ? 'over' : (value === max ? 'complete' : 'partial'));

        note.textContent = 'Maximo ' + formatThousands(max);
        note.classList.toggle('show', value > max);
    }

    /** Corrige vacios y negativos; los valores de mas se dejan en azul y se bloquean al confirmar. */
    function normalize(input) {
        const value = parseInt(input.value, 10);

        input.value = Number.isNaN(value) || value < 0 ? 0 : value;
        paint(input);
    }

    function updateSummary() {
        const approving = quantityInputs().reduce(function (sum, input) {
            return sum + (Number(input.value) || 0);
        }, 0);

        document.getElementById('resApproving').textContent = formatThousands(approving);
    }

    function renderNote(note) {
        currentNote = note;

        document.getElementById('resDocName').textContent = note.code;
        document.getElementById('resDocDate').textContent = dateFmt(note.date);
        document.getElementById('resOrigin').textContent = note.origin;
        document.getElementById('resTransit').textContent = note.transit.name;
        document.getElementById('resDestination').textContent = note.destination.name;

        const requested = note.items.reduce(function (sum, item) { return sum + item.requested; }, 0);
        const maxTotal = note.items.reduce(function (sum, item) { return sum + item.max; }, 0);

        document.getElementById('resRequested').textContent = formatThousands(requested);
        document.getElementById('resAvailable').textContent = formatThousands(maxTotal);

        itemsBody.innerHTML = note.items.map(function (item) {
            const short = item.available < item.requested;

            return `
                <tr>
                    <td class="description-cell">${esc(item.description)}</td>
                    <td>${esc(item.reference || '-')}</td>
                    <td>${esc(item.color || '-')}</td>
                    <td>${esc(item.size || '-')}</td>
                    <td>${formatThousands(item.requested)}</td>
                    <td class="${short ? 'cover-partial' : 'cover-full'}">${formatThousands(item.available)}</td>
                    <td>
                        <input type="number" class="qty-input" min="0" max="${item.max}" step="1"
                               value="${item.max}" data-code="${esc(item.code)}" ${item.max === 0 ? 'disabled' : ''}>
                        <div class="qty-note"></div>
                        <span class="qty-actions">
                            <button type="button" data-fill="max" ${item.max === 0 ? 'disabled' : ''}>Todo</button>
                            <button type="button" data-fill="zero" ${item.max === 0 ? 'disabled' : ''}>0</button>
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        document.getElementById('resImages').innerHTML = (note.images || []).map(function (card) {
            const colors = card.Colors.map(function (color) { return `<div class="image-color">${esc(color.Color)}</div>`; }).join('');
            const picture = card.Image
                ? `<img src="${esc(card.Image)}" alt="${esc(card.Reference)}" loading="lazy">`
                : '<div class="no-image">Sin imagen</div>';

            return `<div class="image-card">${picture}<div class="image-ref">${esc(card.Reference)}</div>${colors}</div>`;
        }).join('');

        quantityInputs().forEach(paint);
        updateSummary();
        resultCard.classList.add('show');
    }

    itemsBody.addEventListener('input', function (event) {
        if (event.target.classList.contains('qty-input')) {
            paint(event.target);
            updateSummary();
        }
    });

    itemsBody.addEventListener('change', function (event) {
        if (event.target.classList.contains('qty-input')) {
            normalize(event.target);
            updateSummary();
        }
    });

    itemsBody.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-fill]');

        if (!button) {
            return;
        }

        const input = button.closest('tr').querySelector('.qty-input');
        input.value = button.dataset.fill === 'max' ? input.max : 0;
        paint(input);
        updateSummary();
    });

    function resetView() {
        currentNote = null;
        itemsBody.innerHTML = '';
        document.getElementById('resImages').innerHTML = '';
        resultCard.classList.remove('show');
        queryInput.value = '';
        queryInput.focus();
    }

    document.getElementById('btnReset').addEventListener('click', resetView);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const token = tokenInput.value.trim();
        const query = queryInput.value.trim();

        if (!token || !query) {
            return;
        }

        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        btnLabel.textContent = 'Buscando...';
        resultCard.classList.remove('show');

        Swal.fire({
            title: 'Consultando nota de traslado',
            text: 'Revisando la nota y lo disponible en la bodega de transito...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () { Swal.showLoading(); }
        });

        try {
            const data = await post(SEARCH_URL, { token: token, query: query });

            renderNote(data.note);

            Swal.fire({ icon: 'success', title: 'Nota encontrada', timer: 1200, showConfirmButton: false });
        } catch (error) {
            Swal.fire({ icon: 'warning', title: 'No se puede continuar', text: error.message, confirmButtonColor: '#16a34a' });
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('is-loading');
            btnLabel.textContent = 'Buscar';
        }
    });

    confirmBtn.addEventListener('click', async function () {
        if (!currentNote) {
            return;
        }

        const over = quantityInputs().find(function (input) {
            return (parseInt(input.value, 10) || 0) > (Number(input.max) || 0);
        });

        if (over) {
            Swal.fire({
                icon: 'warning',
                title: 'Unidades de mas',
                text: 'No puedes aprobar mas unidades de las permitidas (en azul). Ajusta la cantidad e intenta de nuevo.',
                confirmButtonColor: '#16a34a'
            });
            over.focus();
            return;
        }

        const items = quantityInputs().map(function (input) {
            return { code: input.dataset.code, quantity: parseInt(input.value, 10) || 0 };
        });

        const total = items.reduce(function (sum, item) { return sum + item.quantity; }, 0);

        if (total === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin unidades', text: 'Debes aprobar al menos una unidad.', confirmButtonColor: '#16a34a' });
            return;
        }

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Confirmar aprobacion',
            html: 'Se creara el traslado de <strong>' + esc(currentNote.transit.name) + '</strong> a <strong>' +
                esc(currentNote.destination.name) + '</strong> por <strong>' + formatThousands(total) + '</strong> unidades.',
            showCancelButton: true,
            confirmButtonText: 'Si, crear traslado',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#16a34a'
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        confirmBtn.disabled = true;

        Swal.fire({
            title: 'Enviando aprobacion',
            text: 'Por favor espera un momento...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () { Swal.showLoading(); }
        });

        try {
            const data = await post(CONFIRM_URL, {
                token: tokenInput.value.trim(),
                query: currentNote.code,
                items: items
            });

            await Swal.fire({ icon: 'success', title: 'Aprobacion recibida', text: data.message, confirmButtonColor: '#16a34a' });

            resetView();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'No se pudo confirmar', text: error.message, confirmButtonColor: '#d33' });
        } finally {
            confirmBtn.disabled = false;
        }
    });

})();
</script>
@endpush
