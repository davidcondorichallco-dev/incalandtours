<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="app-base-url" content="{{ url('/') }}"><title>Ingresar · Incaland Tours</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
    <div class="login-visual"><img src="{{ asset('images/bolivia-hero.png') }}" alt="Salar de Uyuni"><div></div><a class="brand public-brand" href="{{ route('home') }}"><span class="brand-mark"><span></span></span><span><strong>INCALAND</strong><small>TOURS · BOLIVIA</small></span></a><div class="login-quote"><i>“</i><p>Cada salida empieza con una buena coordinación.</p><small>EQUIPO INCALAND · BOLIVIA</small></div></div>
    <main class="login-main"><div class="login-box"><span class="step-mark">PORTAL INTERNO</span><h1>Bienvenido de nuevo</h1><p>Ingresa para gestionar las operaciones de tu sucursal.</p>
        <form method="POST" action="{{ route('login.attempt') }}" data-login-form data-retry-after="{{ (int) session('retry_after', 0) }}">@csrf
            <label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
            <label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
            @error('email')<div class="login-error">{{ $message }}</div>@enderror
            <button class="primary full" type="submit" data-login-button>Ingresar al sistema <span>→</span></button>
        </form>
        <div class="demo-note"><b>Acceso de demostración</b><span>Admin: maria@incaland.bo · maria1234</span><span>Recepción: daniel@incaland.bo · daniel1234</span><span>Recepción: camila@incaland.bo · camila1234</span></div>
        <a href="{{ route('tourist.online') }}" class="login-public">¿Eres viajero? Reserva tu aventura aquí →</a>
    </div></main>
</body></html>
