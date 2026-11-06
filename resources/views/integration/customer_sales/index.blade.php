@extends('layouts.integration')

@section('title', 'Clientes por venta')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/sync-unico.css') }}">
<style>
    .cs-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
    .cs-req { color: #dc2626; font-weight: 700; }
    .cs-hint { font-size: .75rem; color: #6b7280; margin-top: .6rem; }
    .cs-form label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
    .cs-form input, .cs-form select { width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db; border-radius: 10px; font-size: .9rem; background: #fff; }
    .cs-customer { border: 1px solid #eef0f2; border-radius: 12px; padding: 1rem 1.2rem; margin-top: .8rem; }
    .cs-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; }
    .cs-name { font-weight: 700; color: #1f2937; }
    .cs-meta { font-size: .82rem; color: #6b7280; }
    .cs-phone { font-size: 1.15rem; font-weight: 700; color: #166534; }
    .cs-phone.invalid { color: #991b1b; }
    .cs-group-title { margin-top: 1.4rem; font-weight: 700; font-size: .95rem; color: #374151; }
    .cs-invoice { margin-top: .7rem; font-size: .82rem; }
    .cs-invoice-title { font-weight: 600; color: #374151; }
    .cs-msg label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin: .8rem 0 .3rem; }
    .cs-msg textarea, .cs-msg input[type=file] { width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db; border-radius: 10px; font-size: .9rem; background: #fff; }
    .cs-msg textarea { min-height: 110px; resize: vertical; }
    .cs-preview { max-height: 140px; max-width: 100%; border-radius: 10px; margin-top: .6rem; display: none; }
    .cs-wa { padding: .45rem .9rem; border: 0; border-radius: 10px; background: #16a34a; color: #fff; font-weight: 600; font-size: .85rem; cursor: pointer; }
    .cs-wa:disabled { background: #9ca3af; cursor: not-allowed; }
    .cs-wa-note { font-size: .75rem; color: #6b7280; margin-top: .3rem; }
    .cs-wa-ok { font-size: .75rem; color: #166534; margin-top: .3rem; max-width: 260px; }
    .cs-wa-text { margin-top: .3rem; padding: .3rem .7rem; border: 1px solid #16a34a; border-radius: 8px; background: #fff; color: #166534; font-weight: 600; font-size: .78rem; cursor: pointer; }
</style>
@endpush

@php
    $fmtMoney = fn ($n) => '$' . number_format($n, 0, ',', '.');
    $fmtQty = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
<div class="su-wrap">

    <div class="su-card">
        <div class="excel-upload-title">Clientes por venta</div>
        <div class="excel-upload-subtitle">
            Consulta qué clientes compraron en una fecha, con su teléfono y lo que compraron.
            Sirve, por ejemplo, para ver quién compró este mismo día el año pasado.
            Se consulta un solo día. El token de Siigo solo valida tu acceso.
        </div>

        @if ($errors->any())
            <div class="excel-status su-error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('siigo.customer_sales_search') }}" method="POST" id="csForm">
            @csrf
            <div class="cs-form">
                <div style="grid-column: 1 / -1;">
                    <label for="token">Token de Siigo (valida tu acceso) <span class="cs-req" title="Obligatorio">*</span></label>
                    <input type="text" id="token" name="token" value="{{ request('token') }}" placeholder="Pega aquí el token" autocomplete="off" required>
                </div>
                <div>
                    <label for="date">Fecha <span class="cs-req" title="Obligatorio">*</span></label>
                    <input type="date" id="date" name="date" value="{{ old('date', request('date', now()->subYear()->format('Y-m-d'))) }}" required>
                </div>
                <div>
                    <label for="cost_center">Centro de costo (tienda)</label>
                    <select id="cost_center" name="cost_center">
                        <option value="">Todos</option>
                        @foreach ($costCenters as $center)
                            <option value="{{ $center['id'] }}" {{ (string) old('cost_center', request('cost_center')) === (string) $center['id'] ? 'selected' : '' }}>{{ $center['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="excel-submit-btn" id="csBtn">Consultar</button>
                </div>
            </div>
        </form>
        <div class="cs-hint"><span class="cs-req">*</span> Campo obligatorio</div>
    </div>

    @if (is_array($customers))
        <div class="su-card cs-msg">
            <div class="excel-upload-title">Mensaje por WhatsApp</div>
            <div class="excel-upload-subtitle">
                La foto no se guarda en el servidor: solo se usa en esta pantalla. En el mensaje escribe <strong>{nombre}</strong>
                y se cambia por el nombre de cada cliente. El botón de cada cliente copia la foto y el texto al portapapeles
                y abre su chat de WhatsApp vacío; ahí pega con Ctrl + V y se envían juntos.
            </div>

            <label for="waImage">Foto (opcional)</label>
            <input type="file" id="waImage" accept="image/*">
            <img id="waPreview" class="cs-preview" alt="Vista previa de la foto">

            <label for="waText">Mensaje</label>
            <textarea id="waText" placeholder="Hola {nombre}, hace un año nos visitaste y queremos mostrarte…"></textarea>
        </div>

        @php
            // Primero los clientes con teléfono válido y después los demás
            $groups = [
                'Teléfonos válidos' => array_values(array_filter($customers, fn ($c) => $c['phone_valid'])),
                'Teléfonos no válidos o sin teléfono' => array_values(array_filter($customers, fn ($c) => !$c['phone_valid'])),
            ];
        @endphp

        <div class="su-card">
            <div class="excel-upload-title">
                {{ count($customers) }} {{ count($customers) === 1 ? 'cliente' : 'clientes' }}
            </div>

            @foreach ($groups as $title => $group)
                @continue(empty($group))

                <div class="cs-group-title">{{ $title }} ({{ count($group) }})</div>

                @foreach ($group as $customer)
                    <div class="cs-customer">
                        <div class="cs-head">
                            <div>
                                <div class="cs-name">{{ $customer['name'] }}</div>
                                <div class="cs-phone {{ $customer['phone_valid'] ? '' : 'invalid' }}">{{ $customer['phone'] ?: 'Sin teléfono' }}</div>
                                @if ($customer['email'])
                                    <div class="cs-meta">{{ $customer['email'] }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="cs-meta">{{ count($customer['invoices']) }} {{ count($customer['invoices']) === 1 ? 'factura' : 'facturas' }}</div>
                                @php
                                    // Solo celulares: el primero válido de la lista
                                    $mobile = collect(explode(' / ', $customer['phone']))->first(fn ($number) => preg_match('/^3\d{9}$/', $number));
                                @endphp
                                @if ($customer['phone_valid'] && $mobile)
                                    <button type="button" class="cs-wa" data-wa-name="{{ $customer['name'] }}" data-wa-phone="{{ $mobile }}">Enviar mensaje por WhatsApp</button>
                                    <div class="cs-wa-ok" hidden>
                                        <span data-wa-status></span>
                                        <button type="button" class="cs-wa-text" data-wa-copy-text data-wa-name="{{ $customer['name'] }}" hidden>Copiar mensaje</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @foreach ($customer['invoices'] as $invoice)
                            <div class="cs-invoice">
                                <div class="cs-invoice-title">
                                    {{ $invoice['name'] }} · {{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }} · {{ $fmtMoney($invoice['total']) }}
                                </div>
                                <div class="su-table-wrap">
                                    <table class="su-table">
                                        <thead>
                                            <tr><th>Referencia</th><th>Descripción</th><th class="num">Cantidad</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice['items'] as $item)
                                                <tr>
                                                    <td>{{ $item['code'] }}</td>
                                                    <td>{{ $item['description'] }}</td>
                                                    <td class="num">{{ $fmtQty($item['quantity']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach

            @if (empty($customers))
                <div class="su-empty">No hay ventas para esa fecha y centro de costo.</div>
            @endif
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Evita lanzar la consulta dos veces mientras Siigo responde
    document.getElementById('csForm').addEventListener('submit', () => {
        const btn = document.getElementById('csBtn');
        btn.disabled = true;
        btn.textContent = 'Consultando…';
    });

    // Mensaje por WhatsApp: la foto vive solo en el navegador y nunca se sube al servidor
    (() => {
        const input = document.getElementById('waImage');
        const preview = document.getElementById('waPreview');
        const text = document.getElementById('waText');
        if (!input) return;

        let image = null; // Blob PNG (el portapapeles solo acepta PNG)

        const toPng = (file) => new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                canvas.getContext('2d').drawImage(img, 0, 0);
                URL.revokeObjectURL(url);
                canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('png')), 'image/png');
            };
            img.onerror = () => reject(new Error('imagen'));
            img.src = url;
        });

        input.addEventListener('change', async () => {
            image = null;
            preview.style.display = 'none';
            if (!input.files[0]) return;

            try {
                image = await toPng(input.files[0]);
                preview.src = URL.createObjectURL(image);
                preview.style.display = 'block';
            } catch (e) {
                input.value = '';
                alert('No se pudo leer la imagen. Prueba con otra.');
            }
        });

        // "JUAN PEREZ" -> "Juan Perez"
        const title = (name) => name.toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());

        const messageFor = (name) => text.value.replace(/\{nombre\}/gi, title(name));

        const failed = () => alert('No se pudo copiar al portapapeles. La página debe abrirse con https (o localhost) y permitir el acceso al portapapeles.');

        document.addEventListener('click', async (event) => {
            // Segundo paso: el mensaje, para pegarlo en el pie de la foto
            const copyText = event.target.closest('[data-wa-copy-text]');
            if (copyText) {
                try {
                    await navigator.clipboard.writeText(messageFor(copyText.dataset.waName));
                    copyText.textContent = 'Mensaje copiado: pégalo en el pie de la foto';
                } catch (e) {
                    failed();
                }
                return;
            }

            const btn = event.target.closest('[data-wa-phone]');
            if (!btn) return;

            const message = messageFor(btn.dataset.waName);

            if (!message.trim() && !image) {
                alert('Escribe el mensaje o carga una foto primero.');
                return;
            }

            // WhatsApp toma solo la foto cuando el portapapeles trae foto y texto: se copia la foto primero
            // y el mensaje se copia después con su botón. Sin foto se copia el mensaje directamente.
            try {
                if (image) {
                    await navigator.clipboard.write([new ClipboardItem({ 'image/png': image })]);
                } else {
                    await navigator.clipboard.writeText(message);
                }
            } catch (e) {
                failed();
                return;
            }

            const box = btn.nextElementSibling;
            const second = box.querySelector('[data-wa-copy-text]');
            box.hidden = false;
            second.hidden = !image || !message.trim();
            second.textContent = 'Copiar mensaje';
            box.querySelector('[data-wa-status]').textContent = image
                ? 'Foto copiada: abre el chat y pega con Ctrl + V. Luego pulsa «Copiar mensaje» y pégalo en el pie de la foto.'
                : 'Mensaje copiado: abre el chat y pega con Ctrl + V.';

            // Chat directo con el cliente, sin texto en la URL
            window.open('https://wa.me/57' + btn.dataset.waPhone, '_blank');
        });
    })();
</script>
@endpush
