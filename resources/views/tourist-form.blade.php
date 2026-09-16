<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    <title>{{ $online ? 'Reserva tu aventura' : 'Registro de viajero' }} · Incaland Tours</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="traveler-page">
<div class="traveler-visual"><img src="{{ asset('images/bolivia-hero.png') }}" alt="Paisaje del Salar de Uyuni"><div></div><a class="brand public-brand" href="{{ route('home') }}"><span class="brand-mark"><span></span></span><span><strong>INCALAND</strong><small>TOURS · BOLIVIA</small></span></a><div class="visual-copy"><span>VIAJA · DESCUBRE · RECUERDA</span><h1 data-t="hero">Tu aventura<br>comienza aquí.</h1><p data-t="hero_sub">Cuéntanos un poco sobre ti y nosotros nos encargamos del resto.</p></div><small class="visual-foot">LA PAZ · UYUNI · SAJAMA · BOLIVIA</small></div>
<main class="traveler-form-wrap">
    <a href="{{ route('home') }}" class="form-home-link">← Volver al inicio</a>
    <div class="language-switch"><i data-icon="globe"></i><select id="languageSelect" aria-label="Idioma"><option value="es">Español</option><option value="en">English</option><option value="fr">Français</option><option value="pt">Português</option></select></div>
    <div class="form-intro"><span class="step-mark">{{ $online ? 'RESERVA ONLINE' : 'REGISTRO EN SUCURSAL' }}</span><h2 data-t="title">Datos del viajero</h2><p data-t="intro">Necesitamos esta información para preparar tu experiencia de forma segura y personalizada.</p>@if($branch)<div class="branch-label"><i data-icon="store"></i><span><small>SUCURSAL</small><strong>{{ $branch->name }} · {{ $branch->city }}</strong></span></div>@endif</div>
    <form id="touristForm" class="traveler-form">
        <input type="hidden" name="source" value="{{ $online ? 'online' : 'qr' }}">
        @if($branch)<input type="hidden" name="branch_token" value="{{ $branch->qr_token }}">@endif
        <input type="hidden" name="preferred_language" id="preferredLanguage" value="es">
        @if($online)
        <div class="booking-contact form-grid">
            <div class="span-2 traveler-section-title"><span>01</span><div><strong>Datos de contacto</strong><small>Recibirás aquí la información de toda la reserva.</small></div></div>
            <label><span>Nombre de contacto</span><input name="contact_name" required autocomplete="name" placeholder="Ej. Ana Martínez"></label>
            <label><span>Correo electrónico</span><input name="contact_email" type="email" required autocomplete="email" placeholder="ana@email.com"></label>
            <label><span>WhatsApp</span><div class="phone-field"><b>+</b><input name="contact_whatsapp" required type="tel" autocomplete="tel" placeholder="591 700 00000"></div></label>
            <label><span data-t="package">Paquete turístico</span><select name="tour_package_id" required><option value="">Selecciona tu aventura</option>@foreach($packages as $p)<option value="{{ $p->id }}" @selected(($selectedPackage ?? 0)==$p->id)>{{ $p->name }} · Bs {{ number_format($p->price,0) }}</option>@endforeach</select></label>
            <label><span data-t="date">Fecha de salida</span><input type="date" name="tour_date" min="{{ now()->toDateString() }}" required></label>
        </div>
        <div class="travelers-header"><div class="traveler-section-title"><span>02</span><div><strong>Viajeros</strong><small>Puedes agregar a todas las personas de tu grupo.</small></div></div><b id="travelerCount">1 viajero</b></div>
        <div id="travelersContainer">
            <div class="traveler-member" data-index="0"><div class="member-head"><strong>Viajero principal</strong></div><div class="form-grid">
                <label class="span-2"><span data-t="full_name">Nombre completo</span><input name="travelers[0][full_name]" data-field="full_name" required placeholder="Ej. Ana Martínez"></label>
                <label><span data-t="nationality">Nacionalidad</span><input name="travelers[0][nationality]" data-field="nationality" required placeholder="Ej. España"></label>
                <label><span data-t="passport">N.º de pasaporte</span><input name="travelers[0][passport_number]" data-field="passport_number" required placeholder="Ej. PAA123456"></label>
                <label><span data-t="food">Alimentación</span><input name="travelers[0][food_notes]" data-field="food_notes" placeholder="Alergias o preferencia"></label>
                <label><span data-t="size">Talla general</span><select name="travelers[0][apparel_size]" data-field="apparel_size"><option value="">Selecciona</option><option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option></select></label>
                <label><span data-t="height">Estatura (cm)</span><input type="number" name="travelers[0][height_cm]" data-field="height_cm" min="80" max="240" placeholder="Ej. 170"></label>
                <label><span data-t="hotel">Hotel / hospedaje</span><input name="travelers[0][hotel]" data-field="hotel" placeholder="¿Dónde te alojas?"></label>
                <label><span>WhatsApp personal</span><input name="travelers[0][whatsapp]" data-field="whatsapp" type="tel" placeholder="Opcional"></label>
            </div></div>
        </div>
        <button class="add-traveler" type="button" id="addTraveler"><span>+</span> Agregar otro viajero</button>
        @else
        <div class="form-grid">
            <label class="span-2"><span data-t="full_name">Nombre completo</span><input name="full_name" required autocomplete="name" placeholder="Ej. Ana Martínez"></label>
            <label><span data-t="nationality">Nacionalidad</span><input name="nationality" required placeholder="Ej. España"></label>
            <label><span data-t="passport">N.º de pasaporte</span><input name="passport_number" required autocomplete="off" placeholder="Ej. PAA123456"></label>
            <label><span data-t="food">Alimentación</span><input name="food_notes" placeholder="Alergias o preferencia"></label>
            <label><span data-t="size">Talla general</span><select name="apparel_size"><option value="">Selecciona</option><option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option></select></label>
            <label><span data-t="height">Estatura (cm)</span><input type="number" name="height_cm" min="80" max="240" placeholder="Ej. 170"></label>
            <label><span data-t="hotel">Hotel / hospedaje</span><input name="hotel" placeholder="¿Dónde te alojas?"></label>
            <label class="span-2"><span>WhatsApp</span><div class="phone-field"><b>+</b><input name="whatsapp" required type="tel" autocomplete="tel" placeholder="591 700 00000"></div></label>
        </div>
        @endif
        <label class="consent"><input type="checkbox" required><span data-t="consent">Confirmo que la información proporcionada es correcta.</span></label>
        <button class="primary traveler-submit" type="submit"><span data-t="submit">{{ $online ? 'Confirmar mi reserva' : 'Enviar mis datos' }}</span><b>→</b></button>
        <p class="privacy"><i data-icon="lock"></i><span data-t="privacy">Tus datos están protegidos y solo se usarán para coordinar tu viaje.</span></p>
    </form>
    <div class="success-screen" id="successScreen"><div class="success-icon">✓</div><span>¡TODO LISTO!</span><h2 data-t="success">Recibimos tus datos</h2><p id="successMessage"></p><a href="{{ route('home') }}" class="secondary" data-t="back">Volver al inicio</a></div>
</main>
<div class="toast" id="toast"><i>!</i><span></span></div>
</body>
</html>
