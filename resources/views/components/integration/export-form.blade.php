{{--
    Formulario de exportes por correo.
    Props:
      title, subtitle : textos de cabecera
      action          : URL del POST (route(...))
      formId          : id del <form>
      period          : true => muestra "Periodo a consultar" (mes completo / rango manual)
      requireNewEmails: true => las filas de correo agregadas por JS son required
    Slot: campos extra (se renderizan antes del bloque de error general y del boton).
--}}
@props([
    'title',
    'subtitle',
    'action',
    'formId',
    'period' => false,
    'requireNewEmails' => false,
])

<div class="excel-upload-wrapper">
    <div class="excel-upload-card">

        <div class="excel-upload-title">{{ $title }}</div>
        <div class="excel-upload-subtitle">{{ $subtitle }}</div>

        <form action="{{ $action }}" method="POST" id="{{ $formId }}" data-export-form
            data-period="{{ $period ? '1' : '0' }}" data-require-new-emails="{{ $requireNewEmails ? '1' : '0' }}">
            @csrf

            <div class="section-title">Correos de notificación</div>

            <div id="emailsContainer">
                @foreach (old('emails', ['']) as $index => $emailValue)
                    <div class="email-row">
                        <input type="email" name="emails[]"
                            class="excel-field-input email-input @error('emails.' . $index) is-invalid @enderror"
                            placeholder="nombre@correo.com" value="{{ $emailValue }}">
                        <button type="button" class="email-remove-btn" {{ $loop->count <= 1 ? 'disabled' : '' }} title="Quitar correo">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    @error('emails.' . $index)
                        <div class="excel-error show">{{ $message }}</div>
                    @enderror
                @endforeach
            </div>

            <button type="button" class="email-add-btn" id="addEmailBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Agregar otro correo
            </button>

            <div class="excel-field-hint">
                Si no agregas ningún correo, se enviará a la lista de notificación por defecto del equipo.
            </div>

            @error('emails')
                <div class="excel-error show">{{ $message }}</div>
            @enderror

            <hr class="section-divider">

            @if ($period)
                <div class="section-title">Periodo a consultar</div>

                <div class="mode-tabs">
                    <div class="mode-tab active" data-mode="month">Mes completo</div>
                    <div class="mode-tab" data-mode="range">Rango manual</div>
                </div>

                <div class="mode-panel active" id="monthPanel">
                    <div class="excel-field-group">
                        <label for="monthInput" class="excel-field-label">Mes</label>
                        <input type="month" name="month" id="monthInput" class="excel-field-input"
                            value="{{ old('month') }}">
                        <div class="excel-field-hint">
                            Si lo dejas en blanco, se generará el reporte del mes actual.
                        </div>
                    </div>
                </div>

                <div class="mode-panel" id="rangePanel">
                    <div class="date-range-group">
                        <div class="excel-field-group">
                            <label for="createdStart" class="excel-field-label">Fecha inicio</label>
                            <input type="datetime-local" name="created_start" id="createdStart"
                                class="excel-field-input" value="{{ old('created_start') }}">
                        </div>
                    </div>
                    <div class="date-range-group">
                        <div class="excel-field-group">
                            <label for="createdEnd" class="excel-field-label">Fecha fin</label>
                            <input type="datetime-local" name="created_end" id="createdEnd"
                                class="excel-field-input" value="{{ old('created_end') }}">
                        </div>
                    </div>
                    <div class="excel-field-hint">
                        Ambas fechas son obligatorias si usas el rango manual.
                    </div>
                </div>

                @error('month')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror
                @error('created_start')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror
                @error('created_end')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror
            @endif

            {{ $slot }}

            <div class="excel-error" id="formError"></div>

            <button type="submit" class="excel-submit-btn" id="submitBtn">
                Generar reporte
            </button>
        </form>

    </div>

    <x-integration.back-link />
</div>

@push('scripts')
    <script src="{{ asset('integration/js/export-form.js') }}"></script>
@endpush
