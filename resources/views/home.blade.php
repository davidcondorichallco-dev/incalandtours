<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="app-base-url" content="{{ url('/') }}">
    <title>Incaland Tours · Vive Bolivia</title>
    <meta name="description" content="Descubre y reserva experiencias inolvidables por Bolivia con Incaland Tours.">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="public-home">
<header class="public-nav">
    <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><span></span></span><span><strong>INCALAND</strong><small>TOURS · BOLIVIA</small></span></a>
    <nav><a href="#experiencias">Experiencias</a><a href="#como-funciona">Cómo reservar</a><a href="{{ route('login') }}">Acceso al personal</a><a class="primary" href="{{ route('tourist.online') }}">Reservar ahora</a></nav>
</header>
<main>
    <section class="public-hero" data-home-carousel aria-roledescription="carrusel" aria-label="Destinos destacados">
        <div class="hero-slides">
            @forelse($slides as $slide)
            <article class="hero-slide {{ $loop->first ? 'active' : '' }}" data-carousel-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                <img src="{{ asset(ltrim($slide->image_path, '/')) }}" alt="{{ $slide->title }}" onerror="this.onerror=null;this.src='{{ asset('images/bolivia-hero.png') }}'">
                <div class="public-hero-shade"></div>
                <div class="hero-slide-number" aria-hidden="true"><b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b><i></i><small>{{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</small></div>
                <div class="public-hero-copy">
                    <span><i></i> EXPEDICIONES EXTRAORDINARIAS <i></i></span>
                    <h1>{{ $slide->title }}</h1>
                    @if($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
                    <div class="hero-cta"><a class="primary" href="#experiencias">Explorar experiencias <b>↓</b></a><a class="hero-ghost" href="{{ route('tourist.online') }}">Diseñar mi viaje <b>↗</b></a></div>
                </div>
            </article>
            @empty
            <article class="hero-slide active" data-carousel-slide aria-hidden="false">
                <img src="{{ asset('images/bolivia-hero.png') }}" alt="Paisaje del Salar de Uyuni">
                <div class="public-hero-shade"></div>
                <div class="hero-slide-number" aria-hidden="true"><b>01</b><i></i><small>01</small></div>
                <div class="public-hero-copy"><span><i></i> EXPEDICIONES EXTRAORDINARIAS <i></i></span><h1>Paisajes que no caben en una foto.</h1><p>Explora Bolivia con un equipo local que cuida cada detalle de tu viaje.</p><div class="hero-cta"><a class="primary" href="#experiencias">Explorar experiencias <b>↓</b></a><a class="hero-ghost" href="{{ route('tourist.online') }}">Diseñar mi viaje <b>↗</b></a></div></div>
            </article>
            @endforelse
        </div>
        <button class="carousel-arrow previous" type="button" data-carousel-previous aria-label="Imagen anterior">‹</button>
        <button class="carousel-arrow next" type="button" data-carousel-next aria-label="Imagen siguiente">›</button>
        <div class="carousel-dots" aria-label="Elegir imagen">
            @forelse($slides as $slide)<button type="button" class="{{ $loop->first ? 'active' : '' }}" data-carousel-dot="{{ $loop->index }}" aria-label="Mostrar imagen {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
            @empty<button type="button" class="active" data-carousel-dot="0" aria-label="Mostrar imagen 1" aria-current="true"></button>@endforelse
        </div>
        <div class="hero-scroll-cue" aria-hidden="true"><i></i><span>DESCUBRE BOLIVIA</span></div>
        <div class="hero-facts"><span><b>{{ $packages->count() }}</b><small>DESTINOS ACTIVOS</small></span><span><b>365</b><small>SALIDAS AL AÑO</small></span><span><b>4</b><small>IDIOMAS</small></span></div>
    </section>
    <section class="public-section" id="experiencias">
        <div class="public-section-head"><div><span>ELIGE TU PRÓXIMA HISTORIA</span><h2>Experiencias en Bolivia</h2><p>Puedes conocer todos los paquetes sin crear una cuenta. Registra a uno o varios viajeros únicamente cuando decidas reservar.</p></div><div class="public-filters"><button class="active" data-public-filter="">Todos</button>@foreach($categories as $c)<button data-public-filter="{{ $c->id }}">{{ $c->name }}</button>@endforeach</div></div>
        <div class="public-package-grid">
            @foreach($packages as $p)
            <article class="public-package" data-category="{{ $p->tour_category_id }}">
                <div class="public-package-image"><img src="{{ asset(ltrim($p->image_path ?: 'images/bolivia-hero.png','/')) }}" alt="{{ $p->name }}" onerror="this.onerror=null;this.src='{{ asset('images/bolivia-hero.png') }}'"><span style="--tag:{{ $p->category_color }}">{{ $p->category_name }}</span><em>{{ $p->duration_days }} {{ $p->duration_days == 1 ? 'día' : 'días' }}</em></div>
                <div class="public-package-body"><p>{{ strtoupper($p->location) }}</p><h3>{{ $p->name }}</h3><span>{{ $p->description }}</span><footer><div><small>DESDE</small><strong>Bs {{ number_format($p->price,0) }}</strong></div><a href="{{ route('tourist.online',['package'=>$p->id]) }}">Reservar <b>→</b></a></footer></div>
            </article>
            @endforeach
        </div>
    </section>
    <section class="booking-steps" id="como-funciona"><div><span>RESERVAR ES SIMPLE</span><h2>Una reserva, todos tus viajeros.</h2></div><ol><li><b>01</b><span><strong>Elige</strong><small>Selecciona el paquete y la fecha.</small></span></li><li><b>02</b><span><strong>Registra</strong><small>Agrega a cada persona de tu grupo.</small></span></li><li><b>03</b><span><strong>Prepárate</strong><small>Asignamos el equipo al presentarte.</small></span></li></ol></section>
</main>
<footer class="public-footer"><a class="brand" href="#"><span class="brand-mark"><span></span></span><span><strong>INCALAND</strong><small>TOURS · BOLIVIA</small></span></a><p>La Paz · Uyuni · Santa Cruz</p><a href="{{ route('tourist.online') }}">Comenzar una reserva →</a></footer>
</body></html>
