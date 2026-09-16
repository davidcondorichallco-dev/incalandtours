<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    <title>Incaland Tours · Operaciones</title>
    <meta name="description" content="Gestión de reservas, salidas, equipamiento y sucursales de Incaland Tours.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
<aside class="sidebar" id="sidebar">
    <a class="brand" href="#dashboard" data-jump="dashboard" aria-label="Incaland Tours">
        <span class="brand-mark"><span></span></span>
        <span><strong>INCALAND</strong><small>TOURS · BOLIVIA</small></span>
    </a>
    <nav class="side-nav" aria-label="Navegación principal">
        <p>OPERACIÓN</p>
        @if($staff->role === 'admin')
        <a href="#dashboard" class="active" data-view="dashboard"><i data-icon="grid"></i>Resumen</a>
        @endif
        <a href="#departures" data-view="departures"><i data-icon="route"></i>Salidas</a>
        <a href="#reception" class="{{ $staff->role === 'receptionist' ? 'active' : '' }}" data-view="reception"><i data-icon="users"></i>Recepción <b>{{ $reservations->where('status','pending_package')->count() }}</b></a>
        <p>CATÁLOGO</p>
        @if($staff->role === 'admin')
        <a href="#packages" data-view="packages"><i data-icon="map"></i>Paquetes</a>
        <a href="#carousel" data-view="carousel"><i data-icon="image"></i>Carrusel del home</a>
        @endif
        <a href="#equipment" data-view="equipment"><i data-icon="backpack"></i>Equipamiento</a>
        <a href="#lodgings" data-view="lodgings"><i data-icon="bed"></i>Hospedaje</a>
        @if($staff->role === 'admin')
        <p>ADMINISTRACIÓN</p>
        <a href="#branches" data-view="branches"><i data-icon="store"></i>Sucursales</a>
        <a href="#team" data-view="team"><i data-icon="badge"></i>Personal</a>
        <a href="#profile" data-view="profile"><i data-icon="user"></i>Mi perfil</a>
        <a href="{{ route('api.docs') }}" target="_blank" rel="noopener"><i data-icon="book"></i>Documentación API <span class="external-mark">↗</span></a>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="avatar">{{ collect(explode(' ', $staff->full_name))->map(fn($n)=>mb_substr($n,0,1))->take(2)->join('') }}</div><div><strong>{{ $staff->full_name }}</strong><small>{{ $staff->role === 'admin' ? 'Administración' : 'Recepción' }}</small></div>
        <form action="{{ route('logout') }}" method="POST" data-logout-form>@csrf<button class="icon-btn" aria-label="Cerrar sesión" title="Cerrar sesión">↗</button></form>
    </div>
</aside>

<main class="main-area">
    <header class="topbar">
        <button class="mobile-menu icon-btn" id="menuBtn" aria-label="Abrir menú">☰</button>
        <div><p class="eyebrow" id="sectionEyebrow">{{ $staff->role === 'admin' ? 'CENTRO DE OPERACIONES' : 'ATENCIÓN AL VIAJERO' }}</p><h1 id="sectionTitle">{{ $staff->role === 'admin' ? 'Buenos días, '.explode(' ', $staff->full_name)[0] : 'Recepción de turistas' }} <span>👋</span></h1></div>
        <div class="top-actions">
            <a class="public-link" href="{{ route('tourist.online') }}" target="_blank">Reserva online ↗</a>
            <button class="icon-btn notification" aria-label="Notificaciones"><i data-icon="bell"></i><span></span></button>
            <button class="profile-chip" data-jump="{{ $staff->role === 'admin' ? 'profile' : 'reception' }}"><span>{{ collect(explode(' ', $staff->full_name))->map(fn($n)=>mb_substr($n,0,1))->take(2)->join('') }}</span><div><strong>{{ $staff->full_name }}</strong><small>{{ $staff->role === 'admin' ? 'Admin' : 'Recepción' }}</small></div><b>›</b></button>
        </div>
    </header>

    <div class="content">
        @if($staff->role === 'admin')
        <section class="view active" id="view-dashboard">
            <div class="hero-card">
                <img src="{{ asset('images/bolivia-hero.png') }}" alt="Salar de Uyuni al atardecer">
                <div class="hero-shade"></div>
                <div class="hero-copy"><span class="live-pill"><i></i> OPERACIÓN EN VIVO</span><h2>Todo listo para<br>la próxima aventura.</h2><p>{{ now()->translatedFormat('l, d \d\e F') }} · {{ $departures->where('tour_date', now()->addDays(2)->toDateString())->count() }} salidas programadas</p></div>
                <a href="#departures" class="hero-action" data-jump="departures">Ver salidas <span>→</span></a>
            </div>

            <div class="stat-grid">
                <article class="stat-card red"><div><span>Turistas por atender</span><strong>{{ $reservations->where('status','pending_package')->count() }}</strong><small><i></i> Nuevos registros QR</small></div><i data-icon="users"></i></article>
                <article class="stat-card yellow"><div><span>Próximas salidas</span><strong>{{ $departures->where('status','open')->count() }}</strong><small>En los siguientes días</small></div><i data-icon="route"></i></article>
                <article class="stat-card white"><div><span>Reservas online</span><strong>{{ $reservations->where('source','online')->count() }}</strong><small><em>↑</em> Pendientes de equipo</small></div><i data-icon="globe"></i></article>
                <article class="stat-card white"><div><span>Equipo disponible</span><strong>{{ $equipment->sum('available_stock') }}</strong><small>de {{ $equipment->sum('stock') }} unidades</small></div><i data-icon="backpack"></i></article>
            </div>

            <div class="dashboard-grid">
                <article class="panel arrivals-panel">
                    <div class="panel-head"><div><p class="eyebrow">RECIÉN LLEGADOS</p><h3>Turistas por atender</h3></div><a href="#reception" data-jump="reception">Ver todos →</a></div>
                    <div class="arrival-list">
                    @forelse($reservations->whereIn('status',['pending_package','pending_equipment'])->take(4) as $reservation)
                        <button class="arrival-row" data-open-reservation="{{ $reservation->id }}">
                            <span class="country-avatar">{{ mb_substr($reservation->full_name,0,1) }}{{ mb_substr(strrchr($reservation->full_name,' ') ?: '',1,1) }}</span>
                            <span><strong>{{ $reservation->full_name }}</strong><small>{{ $reservation->nationality }} · {{ $reservation->branch_name ?? 'Reserva web' }}</small></span>
                            <em class="status {{ $reservation->status === 'pending_equipment' ? 'amber' : 'red' }}">{{ $reservation->status === 'pending_equipment' ? 'Falta equipo' : 'Nuevo' }}</em>
                            <time>{{ $reservation->created_at ? \Carbon\Carbon::parse($reservation->created_at)->diffForHumans() : '' }}</time><b>›</b>
                        </button>
                    @empty <div class="empty">No hay turistas pendientes.</div> @endforelse
                    </div>
                </article>
                <article class="panel next-panel">
                    <div class="panel-head"><div><p class="eyebrow">PRÓXIMA SALIDA</p><h3>{{ $departures->first()->package_name ?? 'Sin salidas' }}</h3></div><span class="date-tile"><b>{{ $departures->first() ? \Carbon\Carbon::parse($departures->first()->tour_date)->format('d') : '—' }}</b><small>{{ $departures->first() ? strtoupper(\Carbon\Carbon::parse($departures->first()->tour_date)->translatedFormat('M')) : '' }}</small></span></div>
                    @if($departures->first())
                    <div class="route-line"><span><i></i>La Paz</span><b></b><span><i></i>{{ $departures->first()->location }}</span></div>
                    <div class="next-meta"><span><small>TURISTAS</small><strong>{{ $departures->first()->tourists->count() }} confirmados</strong></span><span><small>GUÍA</small><strong>{{ $departures->first()->guide ?? 'Por asignar' }}</strong></span></div>
                    <button class="primary full" data-jump="departures">Gestionar salida <span>→</span></button>
                    @endif
                </article>
            </div>
        </section>
        @endif

        <section class="view {{ $staff->role === 'receptionist' ? 'active' : '' }}" id="view-reception">
            <div class="section-head"><div><p class="eyebrow">SUCURSAL · {{ strtoupper($branches->firstWhere('id',$staff->branch_id)->name ?? 'CENTRAL') }}</p><h2>Recepción de turistas</h2><p>Completa el paquete y equipamiento de cada nuevo registro.</p></div><div class="head-actions"><label class="search"><i data-icon="search"></i><input data-filter="receptionTable" placeholder="Buscar turista o pasaporte"></label></div></div>
            <div class="tabs" id="receptionTabs"><button class="active" data-reception-status="pending">Pendientes <b>{{ $reservations->whereIn('status',['pending_package','pending_equipment'])->count() }}</b></button><button data-reception-status="confirmed">Confirmados</button><button data-reception-status="all">Todos</button></div>
            <div class="table-card"><table id="receptionTable"><thead><tr><th>Turista</th><th>Contacto</th><th>Origen</th><th>Estado</th><th>Registro</th><th></th></tr></thead><tbody>
            @foreach($reservations as $r)<tr data-reservation-status="{{ $r->status }}"><td><span class="person-cell"><i>{{ mb_substr($r->full_name,0,1) }}</i><span><strong>{{ $r->full_name }}</strong><small>{{ $r->nationality }} · {{ $r->passport_number }}</small></span></span></td><td><strong>{{ $r->whatsapp }}</strong><small class="block">{{ $r->hotel ?: 'Hotel no indicado' }}</small></td><td><span class="source-pill {{ $r->source }}">{{ $r->source === 'online' ? 'Web' : ($r->branch_name ?? 'QR') }}</span></td><td><span class="status {{ $r->status === 'confirmed' ? 'green' : ($r->status === 'pending_equipment' ? 'amber' : 'red') }}">{{ ['confirmed'=>'Confirmado','pending_equipment'=>'Falta equipo','pending_package'=>'Nuevo'][$r->status] }}</span></td><td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td><td><button class="row-action" data-open-reservation="{{ $r->id }}">Gestionar →</button></td></tr>@endforeach
            </tbody></table></div>
        </section>

        <section class="view" id="view-departures">
            <div class="section-head"><div><p class="eyebrow">PLANIFICACIÓN DIARIA</p><h2>Salidas</h2><p>{{ $staff->role === 'admin' ? 'Turistas agrupados automáticamente por fecha y paquete.' : 'Consulta las salidas y los viajeros asignados a tu sucursal.' }}</p></div></div>
            <div class="filterbar"><label><span>Fecha</span><input type="date" id="departureDate"></label><label><span>Paquete</span><select id="departurePackage"><option value="">Todos los paquetes</option>@foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></label><label class="search grow"><i data-icon="search"></i><input id="departureSearch" placeholder="Buscar cliente"></label><button class="secondary" id="clearFilters">Limpiar filtros</button></div>
            <div class="departure-list" id="departureList">
            @foreach($departures as $d)
                <article class="departure-card" data-date="{{ $d->tour_date }}" data-package="{{ $d->tour_package_id }}">
                    <div class="departure-date"><b>{{ \Carbon\Carbon::parse($d->tour_date)->format('d') }}</b><span>{{ strtoupper(\Carbon\Carbon::parse($d->tour_date)->translatedFormat('M')) }}</span><small>{{ strtoupper(\Carbon\Carbon::parse($d->tour_date)->translatedFormat('D')) }}</small></div>
                    <div class="departure-main"><div class="departure-title"><div><span class="package-dot"></span><h3>{{ $d->package_name }}</h3><em class="status {{ $d->status === 'closed' ? 'green' : 'amber' }}">{{ $d->status === 'closed' ? 'Cerrada' : 'Abierta' }}</em></div><span>{{ $d->tourists->count() }} turistas</span></div>
                    <div class="tourist-chips">@forelse($d->tourists as $t)<button data-open-reservation="{{ $t->id }}"><i>{{ mb_substr($t->full_name,0,1) }}</i><span><strong>{{ $t->full_name }}</strong><small>{{ $t->branch_name ?? 'Web' }} · {{ $t->status === 'pending_equipment' ? 'Equipo pendiente' : 'Listo' }}</small></span></button>@empty<span class="empty-inline">Aún no hay turistas en esta salida.</span>@endforelse</div></div>
                    <div class="departure-side"><span><small>GUÍA</small><strong>{{ $d->guide ?? 'Por asignar' }}</strong></span><span><small>CONDUCTOR</small><strong>{{ $d->driver ?? 'Por asignar' }}</strong></span>@if($d->status === 'open' && $staff->role === 'admin')<button class="primary" data-close-departure="{{ $d->id }}">Completar y cerrar</button>@elseif($d->status === 'closed')<button class="secondary" data-departure-summary="{{ $d->package_name }} · Guía: {{ $d->guide }} · Conductor: {{ $d->driver }}">Ver detalle</button>@else<em class="status amber">Solo consulta</em>@endif</div>
                </article>
            @endforeach
            </div>
        </section>

        @if($staff->role === 'admin')
        <section class="view" id="view-packages">
            <div class="section-head"><div><p class="eyebrow">EXPERIENCIAS</p><h2>Paquetes turísticos</h2><p>Administra destinos, precios y categorías.</p></div><div class="head-actions"><button class="secondary" data-new="categories">+ Categoría</button><button class="primary" data-new="packages">+ Nuevo paquete</button></div></div>
            <div class="catalog-grid">@foreach($packages as $p)<article class="package-card"><div class="package-image"><img src="{{ asset(ltrim($p->image_path ?: 'images/bolivia-hero.png','/')) }}" alt="{{ $p->name }}" onerror="this.onerror=null;this.src='{{ asset('images/bolivia-hero.png') }}'"><span style="--tag:{{ $p->category_color }}">{{ $p->category_name }}</span><button data-edit="packages" data-id="{{ $p->id }}" aria-label="Editar {{ $p->name }}">Editar</button></div><div><p>{{ $p->location }} · {{ $p->duration_days }} {{ $p->duration_days == 1 ? 'día' : 'días' }}</p><h3>{{ $p->name }}</h3><small>{{ $p->description }}</small><footer><strong>Bs {{ number_format($p->price,0) }}</strong><em class="status green">Activo</em></footer></div></article>@endforeach</div>
            <div class="category-strip"><strong>Categorías</strong>@foreach($categories as $c)<button data-edit="categories" data-id="{{ $c->id }}"><i style="background:{{ $c->color }}"></i>{{ $c->name }} <span>Editar</span></button>@endforeach</div>
        </section>

        <section class="view" id="view-carousel">
            <div class="section-head"><div><p class="eyebrow">PORTADA DEL SITIO</p><h2>Carrusel del home</h2><p>Administra las imágenes y mensajes que aparecen al inicio de la página pública.</p></div><button class="primary" data-new="slides">+ Nueva diapositiva</button></div>
            <div class="carousel-admin-grid">
                @forelse($slides as $slide)
                <article class="carousel-admin-card">
                    <div class="carousel-admin-image">
                        <img src="{{ asset(ltrim($slide->image_path, '/')) }}" alt="{{ $slide->title }}" onerror="this.onerror=null;this.src='{{ asset('images/bolivia-hero.png') }}'">
                        <span class="status {{ $slide->active ? 'green' : 'amber' }}">{{ $slide->active ? 'Visible' : 'Oculta' }}</span>
                        <b>Orden {{ $slide->display_order }}</b>
                    </div>
                    <div class="carousel-admin-content"><h3>{{ $slide->title }}</h3><p>{{ $slide->subtitle ?: 'Sin subtítulo' }}</p><footer><button class="row-action" data-edit="slides" data-id="{{ $slide->id }}">Editar</button><button class="danger-action" data-delete-slide="{{ $slide->id }}" data-slide-title="{{ $slide->title }}">Eliminar</button></footer></div>
                </article>
                @empty
                <div class="carousel-admin-empty"><span>＋</span><h3>Aún no hay diapositivas</h3><p>Agrega la primera imagen para personalizar la portada.</p><button class="primary" data-new="slides">Agregar diapositiva</button></div>
                @endforelse
            </div>
        </section>
        @endif

        <section class="view" id="view-equipment">
            <div class="section-head"><div><p class="eyebrow">INVENTARIO</p><h2>Equipamiento</h2><p>{{ $staff->role === 'admin' ? 'Disponibilidad y estado del equipo para las salidas.' : 'Consulta la disponibilidad antes de asignar equipo a un turista.' }}</p></div>@if($staff->role === 'admin')<button class="primary" data-new="equipment">+ Registrar equipo</button>@endif</div>
            <div class="inventory-summary"><span><b>{{ $equipment->sum('stock') }}</b><small>Unidades totales</small></span><span><b>{{ $equipment->sum('available_stock') }}</b><small>Disponibles</small></span><span><b>{{ $equipment->sum('stock')-$equipment->sum('available_stock') }}</b><small>Asignadas</small></span></div>
            <div class="equipment-grid">@foreach($equipment as $e)<article class="equipment-card"><div class="equipment-icon">@if($e->image_path)<img src="{{ asset(ltrim($e->image_path,'/')) }}" alt="{{ $e->name }}" onerror="this.remove()">@else<i data-icon="{{ str_contains(strtolower($e->name),'bici') ? 'bike' : (str_contains(strtolower($e->name),'casco') ? 'helmet' : 'backpack') }}"></i>@endif</div><div><small>{{ strtoupper($e->category) }}</small><h3>{{ $e->name }}</h3><p>Tallas: {{ $e->size ?: 'Única' }}</p><div class="stockline"><span><i style="width:{{ $e->stock ? ($e->available_stock/$e->stock*100) : 0 }}%"></i></span><strong>{{ $e->available_stock }}/{{ $e->stock }}</strong></div><footer><em class="status green">{{ $e->condition }}</em>@if($staff->role === 'admin')<button data-edit="equipment" data-id="{{ $e->id }}">Editar</button>@else<small>Solo lectura</small>@endif</footer></div></article>@endforeach</div>
        </section>

        <section class="view" id="view-lodgings">
            <div class="section-head"><div><p class="eyebrow">HOSPEDAJE PROPIO</p><h2>Hospedajes</h2><p>{{ $staff->role === 'admin' ? 'Control centralizado de habitaciones y disponibilidad.' : 'Consulta habitaciones disponibles para orientar al turista.' }}</p></div>@if($staff->role === 'admin')<button class="primary" data-new="lodgings">+ Nuevo hospedaje</button>@endif</div>
            <div class="simple-grid">@foreach($lodgings as $l)<article class="info-card">@if($l->image_path)<span class="big-icon image"><img src="{{ asset(ltrim($l->image_path,'/')) }}" alt="{{ $l->name }}"></span>@else<span class="big-icon"><i data-icon="bed"></i></span>@endif<div><p>{{ strtoupper($l->city) }}</p><h3>{{ $l->name }}</h3><small>{{ $l->address }}</small></div><footer><span><b>{{ $l->available_rooms }}</b> disponibles de {{ $l->rooms }}</span>@if($staff->role === 'admin')<button class="row-action" data-edit="lodgings" data-id="{{ $l->id }}">Editar</button>@else<em class="status green">Solo consulta</em>@endif</footer></article>@endforeach</div>
        </section>

        @if($staff->role === 'admin')
        <section class="view" id="view-branches">
            <div class="section-head"><div><p class="eyebrow">PUNTOS DE ATENCIÓN</p><h2>Sucursales y códigos QR</h2><p>Cada QR abre el formulario conectado a su sucursal.</p></div><button class="primary" data-new="branches">+ Nueva sucursal</button></div>
            <div class="simple-grid">@foreach($branches as $b)<article class="branch-card"><div><span class="big-icon"><i data-icon="store"></i></span><em class="status green">Activa</em></div><p>{{ strtoupper($b->city) }}</p><h3>{{ $b->name }}</h3><small>{{ $b->address }} · {{ $b->phone }}</small><footer><button class="secondary" data-qr="{{ route('tourist.qr',$b->qr_token) }}" data-branch="{{ $b->name }}"><i data-icon="qr"></i> Ver QR</button><button class="row-action" data-edit="branches" data-id="{{ $b->id }}">Editar</button><a href="{{ route('tourist.qr',$b->qr_token) }}" target="_blank">Abrir ↗</a></footer></article>@endforeach</div>
        </section>

        <section class="view" id="view-team">
            <div class="section-head"><div><p class="eyebrow">EQUIPO</p><h2>Personal</h2><p>Recepcionistas asignados a cada sucursal.</p></div><button class="primary" data-new="employees">+ Nuevo empleado</button></div>
            <div class="table-card"><table><thead><tr><th>Empleado</th><th>Rol</th><th>Sucursal</th><th>Contacto</th><th>Estado</th><th></th></tr></thead><tbody>@foreach($employees as $e)<tr><td><span class="person-cell"><i>{{ mb_substr($e->full_name,0,1) }}</i><span><strong>{{ $e->full_name }}</strong><small>{{ $e->email }}</small></span></span></td><td>{{ $e->role === 'admin' ? 'Administración' : 'Recepción' }}</td><td>{{ $branches->firstWhere('id',$e->branch_id)->name ?? '—' }}</td><td>{{ $e->phone }}</td><td><em class="status green">Activo</em></td><td><button class="row-action" data-edit="employees" data-id="{{ $e->id }}">Editar</button></td></tr>@endforeach</tbody></table></div>
        </section>

        <section class="view" id="view-profile">
            <div class="section-head"><div><p class="eyebrow">CUENTA DE ADMINISTRACIÓN</p><h2>Mi perfil</h2><p>Actualiza tus datos de acceso. Para guardar cualquier cambio debes confirmar tu contraseña actual.</p></div><a class="primary api-doc-button" href="{{ route('api.docs') }}" target="_blank" rel="noopener"><i data-icon="book"></i> Ver documentación API ↗</a></div>
            <div class="profile-layout">
                <form id="profileForm" class="profile-card">
                    <div class="profile-card-head"><span class="avatar large">{{ collect(explode(' ', $staff->full_name))->map(fn($n)=>mb_substr($n,0,1))->take(2)->join('') }}</span><div><h3>Datos de la cuenta</h3><p>Estos datos se usarán en tu próximo inicio de sesión.</p></div></div>
                    <div class="form-grid">
                        <label class="span-2">Nombre completo<input name="full_name" value="{{ $staff->full_name }}" maxlength="150" required autocomplete="name"></label>
                        <label>Nombre de usuario<input name="username" value="{{ $staff->username }}" minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]+" required autocomplete="username"></label>
                        <label>Correo electrónico<input name="email" type="email" value="{{ $staff->email }}" maxlength="150" required autocomplete="email"></label>
                    </div>
                    <div class="profile-passwords">
                        <div><h3>Seguridad</h3><p>Deja la nueva contraseña vacía si no deseas cambiarla.</p></div>
                        <div class="form-grid">
                            <label class="span-2">Contraseña actual<input name="current_password" type="password" required autocomplete="current-password"></label>
                            <label>Nueva contraseña<input name="password" type="password" minlength="8" autocomplete="new-password"></label>
                            <label>Confirmar nueva contraseña<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
                        </div>
                    </div>
                    <button class="primary" type="submit" data-profile-submit>Guardar cambios</button>
                </form>
                <aside class="security-note"><i data-icon="lock"></i><h3>Protección de la cuenta</h3><p>Si cambias la contraseña, se cerrarán los accesos API anteriores para proteger la cuenta.</p><span>Usa al menos 8 caracteres y evita reutilizar contraseñas.</span></aside>
            </div>
        </section>
        @endif
    </div>
</main>

<div class="modal" id="reservationModal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><div class="modal-card wide"><button class="modal-x" data-close-modal>×</button><div id="reservationContent"></div></div></div>
@if($staff->role === 'admin')
<div class="modal" id="departureModal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><div class="modal-card"><button class="modal-x" data-close-modal>×</button><p class="eyebrow">CIERRE DE SALIDA</p><h2>Completar datos generales</h2><p class="modal-lead">Estos datos se aplicarán a todos los turistas de la salida.</p><form id="departureForm"><input type="hidden" name="departure_id"><div class="form-grid"><label class="span-2">Incluye<textarea name="includes" required placeholder="Transporte, alimentación, entradas..."></textarea></label><label class="span-2">No incluye<textarea name="excludes" required placeholder="Bebidas, gastos personales..."></textarea></label><label>Guía<input name="guide" required placeholder="Nombre completo"></label><label>Conductor<input name="driver" required placeholder="Nombre completo"></label><label class="span-2">Observaciones<textarea name="observations" placeholder="Indicaciones especiales"></textarea></label></div><button class="primary full" type="submit">Confirmar y cerrar salida</button></form></div></div>
<div class="modal" id="resourceModal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><div class="modal-card"><button class="modal-x" data-close-modal>×</button><p class="eyebrow">GESTIÓN DE CATÁLOGO</p><h2 id="resourceTitle">Nuevo registro</h2><form id="resourceForm"><input type="hidden" name="resource_type"><input type="hidden" name="resource_id"><div class="form-grid" id="resourceFields"></div><button class="primary full" type="submit">Guardar cambios</button></form></div></div>
<div class="modal" id="qrModal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><div class="modal-card qr-card"><button class="modal-x" data-close-modal>×</button><p class="eyebrow">CÓDIGO DE SUCURSAL</p><h2 id="qrTitle"></h2><div class="qr-frame"><img id="qrImage" alt="Código QR de registro"></div><p>El turista debe escanear este código desde su teléfono para registrarse.</p><a class="primary full" id="qrLink" target="_blank">Abrir formulario de registro</a></div></div>
@endif
<div class="toast" id="toast"><i>✓</i><span>Guardado correctamente</span></div>

<script>
window.Incaland = {{ Illuminate\Support\Js::from([
    'reservations' => $reservations,
    'packages' => $packages,
    'equipment' => $equipment,
    'branches' => $staff->role === 'admin' ? $branches : [],
    'categories' => $staff->role === 'admin' ? $categories : [],
    'employees' => $staff->role === 'admin' ? $employees : [],
    'lodgings' => $staff->role === 'admin' ? $lodgings : [],
    'slides' => $staff->role === 'admin' ? $slides : [],
    'staff' => ['full_name'=>$staff->full_name, 'role'=>$staff->role],
    'defaultView' => $staff->role === 'admin' ? 'dashboard' : 'reception',
]) }};
</script>
</body>
</html>
