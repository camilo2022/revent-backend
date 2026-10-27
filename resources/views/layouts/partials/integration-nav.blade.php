{{--
    Barra superior del modulo de integracion con Siigo: solo la marca, que lleva al inicio.
    Las acciones disponibles viven en las tarjetas de integration/home.blade.php.
--}}
<header class="rv-topbar">
    <div class="rv-topbar-inner">
        <a href="{{ route('home') }}" class="rv-brand">
            <img src="{{ asset('integration/img/revent-logo.png') }}" alt="Revent" class="rv-logo">
            <img src="{{ asset('integration/img/siigo-logo.png') }}" alt="Siigo" class="rv-logo rv-logo--siigo">
        </a>
    </div>
</header>
