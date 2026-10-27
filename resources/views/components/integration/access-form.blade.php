{{--
    Pantalla de acceso por correo (enlace firmado).
    Props: title (nombre del modulo), action (URL del POST), hint (texto bajo el campo).
--}}
@props(['title', 'action', 'hint'])

<div class="excel-upload-wrapper excel-upload-wrapper--narrow">
    <div class="excel-upload-card">

        <div class="excel-upload-title">{{ $title }}</div>
        <div class="excel-upload-subtitle">
            Escribe tu correo autorizado y te enviaremos un enlace de acceso.
        </div>

        @if (session('status'))
            <div class="excel-status">{{ session('status') }}</div>
        @endif

        <form action="{{ $action }}" method="POST" id="accessForm">
            @csrf

            <div class="excel-field-group">
                <label for="email" class="excel-field-label">
                    Correo electrónico <span class="required-mark">*</span>
                </label>
                <input type="email" name="email" id="email"
                    class="excel-field-input @error('email') is-invalid @enderror"
                    placeholder="nombre{{ '@' . config('siigo.mail.domain') }}" value="{{ old('email') }}" required autofocus>
                <div class="excel-field-hint">{{ $hint }}</div>
            </div>

            @error('email')
                <div class="excel-error show">{{ $message }}</div>
            @enderror

            <button type="submit" class="excel-submit-btn" id="submitBtn">
                <span class="btn-spinner"></span>
                <span class="btn-label">Enviar enlace de acceso</span>
            </button>
        </form>

    </div>

    <x-integration.back-link />
</div>

@push('scripts')
    <script src="{{ asset('integration/js/access-form.js') }}"></script>
@endpush
