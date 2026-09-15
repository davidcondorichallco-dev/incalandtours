@php
$base = url('/api/v1');
$groups = [
    'Autenticación' => [
        ['POST','/auth/login','Iniciar sesión','Público','Devuelve un token Bearer válido durante 30 días.','{"email":"maria@incaland.bo","password":"maria1234","device_name":"Flutter Android"}'],
        ['GET','/auth/me','Usuario actual','Token','Devuelve el empleado, rol y sucursal asociados al token.',null],
        ['POST','/auth/logout','Cerrar sesión','Token','Revoca solamente el token enviado en esta solicitud.',null],
    ],
    'Reservas públicas y QR' => [
        ['GET','/public/home','Contenido completo del home','Público','Devuelve carrusel activo, categorías, paquetes e idiomas tal como se muestran en la web.',null],
        ['GET','/public/carousel','Carrusel público','Público','Lista únicamente las diapositivas visibles, ordenadas y con image_url absoluta.',null],
        ['GET','/public/catalog','Catálogo público','Público','Lista categorías, paquetes activos y carrusel.',null],
        ['GET','/public/packages/{id}','Detalle de paquete','Público','Devuelve un paquete turístico activo.',null],
        ['GET','/public/booking','Datos para reservar','Público','Paquetes, idiomas y tallas para construir el formulario Flutter.',null],
        ['GET','/public/qr/{token}','Leer código QR','Público','Valida el QR y devuelve la sucursal y opciones del formulario.',null],
        ['POST','/public/reservations/online','Reserva online','Público','Crea una reserva grupal, viajeros y salida. Máximo 20 viajeros.','{"contact_name":"Ana Martínez","contact_email":"ana@email.com","contact_whatsapp":"+59170000000","tour_package_id":1,"tour_date":"2026-10-15","preferred_language":"es","travelers":[{"full_name":"Ana Martínez","nationality":"Bolivia","passport_number":"BO123456","apparel_size":"M","height_cm":168}]}'],
        ['POST','/public/reservations/qr/{token}','Registro desde QR','Público','Registra un viajero pendiente en la sucursal identificada por el QR.','{"full_name":"Juan Pérez","nationality":"Perú","passport_number":"PE998877","food_notes":"Vegetariano","apparel_size":"L","height_cm":178,"hotel":"Hotel Central","whatsapp":"+51999000111","preferred_language":"es"}'],
    ],
    'Operaciones' => [
        ['GET','/dashboard','Resumen del panel','Token','Indicadores, reservas recientes y próxima salida según el rol.',null],
        ['GET','/reservations','Listar reservas','Token','Filtros opcionales: status, source y search. Recepción solo ve su sucursal y reservas web.',null],
        ['GET','/reservations/{id}','Detalle de reserva','Token','Incluye datos del viajero y equipamiento asignado.',null],
        ['PUT','/reservations/{id}/complete','Completar reserva','Token','Asigna paquete, fecha y equipamiento; luego agrega al viajero a una salida.','{"tour_package_id":1,"tour_date":"2026-10-15","equipment_ids":[2,3],"notes":"Talla verificada"}'],
        ['GET','/departures','Listar salidas','Token','Filtros opcionales: date, package_id y status.',null],
        ['GET','/departures/{id}','Detalle de salida','Token','Devuelve paquete, guía, conductor y viajeros visibles para el rol.',null],
        ['PUT','/departures/{id}/close','Cerrar salida','Administrador','Completa la información operativa y cierra la salida.','{"includes":"Transporte y alimentación","excludes":"Propinas","guide":"Julio Mamani","driver":"Óscar Rojas","observations":"Sin novedades"}'],
    ],
    'Catálogo interno' => [
        ['GET','/catalog/{type}','Listar recursos','Token','Tipos: branches, categories, packages, equipment, lodgings, employees y slides. Employees y slides requieren administrador.',null],
        ['GET','/catalog/{type}/{id}','Detalle de recurso','Token','Devuelve un elemento del catálogo. Nunca expone contraseñas ni el token QR.',null],
        ['POST','/catalog/{type}','Crear recurso','Administrador','Crea cualquier recurso administrable. Packages, equipment, lodgings y slides aceptan imágenes JPG, PNG o WebP de hasta 8 MB usando multipart/form-data.','{"name":"Nueva categoría","color":"#E00022"}'],
        ['PUT','/catalog/{type}/{id}','Actualizar recurso','Administrador','Actualiza un recurso y puede reemplazar su imagen. Para multipart envía POST con _method=PUT.',null],
        ['DELETE','/catalog/slides/{id}','Eliminar diapositiva','Administrador','Elimina la diapositiva y también su archivo si fue subido al almacenamiento del sistema.',null],
        ['GET','/branches/{id}/qr','Obtener QR de sucursal','Administrador','Devuelve token, URL pública, endpoints API y URL de la imagen QR.',null],
    ],
];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>API REST · Incaland Tours</title>
    <style>
        :root{--ink:#17211d;--muted:#64716b;--line:#dfe7e2;--paper:#f5f7f4;--green:#176b4a;--red:#d7193f;--blue:#1769aa;--amber:#a96800;--violet:#7356a8}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.hero{padding:64px max(5vw,24px) 52px;background:linear-gradient(125deg,#10291f,#1b543e);color:#fff;position:relative;overflow:hidden}.hero:after{content:"API";position:absolute;right:5vw;top:-42px;font-size:190px;font-weight:900;color:#ffffff0b;letter-spacing:-12px}.brand{font-size:12px;letter-spacing:3px;font-weight:800;color:#b9dfcb}.hero h1{font-size:clamp(36px,6vw,68px);line-height:.98;margin:24px 0 18px;letter-spacing:-3px}.hero p{max-width:700px;color:#d4e4dc;font-size:18px;line-height:1.6}.base{display:inline-flex;gap:12px;align-items:center;background:#0a1a14;border:1px solid #ffffff25;border-radius:12px;padding:12px 16px;margin-top:12px;font-family:ui-monospace,monospace}.base button,.copy{border:0;background:#ffffff18;color:#fff;border-radius:7px;padding:7px 10px;cursor:pointer}.layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:34px;max-width:1380px;margin:0 auto;padding:36px max(3vw,20px) 80px}.side{position:sticky;top:20px;align-self:start;background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px}.side strong{display:block;font-size:12px;letter-spacing:1.5px;color:var(--muted);margin:6px 8px 12px}.side a{display:block;text-decoration:none;color:var(--ink);padding:10px 12px;border-radius:9px;font-size:14px}.side a:hover{background:#edf5f0;color:var(--green)}main{min-width:0}.intro{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:40px}.info{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}.info b{display:block;margin-bottom:7px}.info span{color:var(--muted);font-size:14px;line-height:1.5}.group{scroll-margin-top:24px;margin-bottom:44px}.group-head{display:flex;align-items:end;justify-content:space-between;margin-bottom:14px}.group h2{font-size:27px;margin:0;letter-spacing:-.8px}.group-head span{color:var(--muted);font-size:13px}.endpoint{background:#fff;border:1px solid var(--line);border-radius:15px;margin:10px 0;overflow:hidden}.endpoint summary{list-style:none;display:grid;grid-template-columns:64px minmax(190px,1fr) minmax(180px,1.2fr) auto;align-items:center;gap:14px;padding:17px 18px;cursor:pointer}.endpoint summary::-webkit-details-marker{display:none}.method{display:inline-flex;justify-content:center;padding:6px 8px;border-radius:7px;color:white;font-size:11px;font-weight:900;letter-spacing:.5px}.GET{background:var(--blue)}.POST{background:var(--green)}.PUT{background:var(--amber)}.DELETE{background:var(--red)}.path{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:14px;font-weight:700;overflow-wrap:anywhere}.endpoint-title{font-size:14px}.auth{font-size:11px;padding:5px 9px;border-radius:99px;background:#eef1ef;color:#52605a}.detail{border-top:1px solid var(--line);padding:18px;background:#fbfcfb}.detail p{margin:0 0 14px;color:var(--muted);line-height:1.55}.code-wrap{position:relative}.code-wrap .copy{position:absolute;right:8px;top:8px;background:#ffffff17}pre{margin:0;background:#132019;color:#d9eee3;border-radius:11px;padding:18px;overflow:auto;font-size:12px;line-height:1.6}.guide{background:#fff;border:1px solid var(--line);border-radius:18px;padding:25px;margin-top:20px}.guide h2{margin-top:0}.guide h3{margin:24px 0 8px}.guide p,.guide li{color:var(--muted);line-height:1.65}.status-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}.status-grid span{background:#f0f3f1;padding:12px;border-radius:9px;text-align:center;font-size:12px}.status-grid b{display:block;color:var(--green);font-size:18px}.search{width:100%;border:1px solid var(--line);padding:11px 13px;border-radius:10px;margin-bottom:10px;font:inherit}@media(max-width:860px){.layout{grid-template-columns:1fr}.side{position:static}.intro{grid-template-columns:1fr}.endpoint summary{grid-template-columns:58px 1fr}.endpoint-title,.auth{grid-column:2}.status-grid{grid-template-columns:repeat(2,1fr)}}
    </style>
</head>
<body>
<header class="hero">
    <div class="brand">INCALAND TOURS · BACKEND</div>
    <h1>API REST </h1>
    <p>Referencia completa de autenticación, home, carrusel, imágenes, reservas, códigos QR, operaciones, salidas y administración del catálogo.</p>
    <div class="base"><span id="baseUrl">{{ $base }}</span><button onclick="copyText(document.getElementById('baseUrl').textContent,this)">Copiar</button></div>
</header>
<div class="layout">
    <aside class="side">
        <strong>NAVEGACIÓN</strong>
        <input class="search" id="search" placeholder="Buscar endpoint…" aria-label="Buscar endpoint">
        @foreach($groups as $name=>$endpoints)<a href="#{{ Str::slug($name) }}">{{ $name }} <small>({{ count($endpoints) }})</small></a>@endforeach
        <a href="#integracion">Integración Flutter</a>
        <a href="#respuestas">Respuestas HTTP</a>
    </aside>
    <main>
        <section class="intro">
            <div class="info"><b>Versión estable</b><span>Todos los endpoints están bajo <code>/api/v1</code>.</span></div>
            <div class="info"><b>Autenticación segura</b><span>Token Bearer con expiración de 30 días y revocación al salir.</span></div>
            <div class="info"><b>Permisos por rol</b><span>Recepción ve su sucursal; administración controla el catálogo y cierres.</span></div>
        </section>

        @foreach($groups as $name=>$endpoints)
        <section class="group" id="{{ Str::slug($name) }}">
            <div class="group-head"><h2>{{ $name }}</h2><span>{{ count($endpoints) }} endpoints</span></div>
            @foreach($endpoints as [$method,$path,$title,$auth,$description,$body])
            @php
                $example = $method.' '.$base.$path."\nAccept: application/json";
                if ($auth !== 'Público') $example .= "\nAuthorization: Bearer TU_TOKEN";
                if ($body) $example .= "\nContent-Type: application/json\n\n".json_encode(json_decode($body), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            @endphp
            <details class="endpoint" data-search="{{ strtolower($method.' '.$path.' '.$title.' '.$description) }}">
                <summary><span class="method {{ $method }}">{{ $method }}</span><span class="path">{{ $path }}</span><span class="endpoint-title">{{ $title }}</span><span class="auth">{{ $auth }}</span></summary>
                <div class="detail">
                    <p>{{ $description }}</p>
                    <div class="code-wrap"><button class="copy" onclick="copyText(this.nextElementSibling.textContent,this)">Copiar</button><pre><code>{{ $example }}</code></pre></div>
                </div>
            </details>
            @endforeach
        </section>
        @endforeach

        <section class="guide" id="integracion">
            <h2>Integración rápida con Flutter</h2>
            <p>Guarda <code>access_token</code> en almacenamiento seguro y envíalo en cada solicitud interna.</p>
            <div class="code-wrap"><button class="copy" onclick="copyText(this.nextElementSibling.textContent,this)">Copiar</button><pre><code>final response = await dio.post(
  '{{ $base }}/auth/login',
  data: {
    'email': 'maria@incaland.bo',
    'password': 'maria1234',
    'device_name': 'Flutter Android',
  },
);

final token = response.data['data']['access_token'];
dio.options.headers['Authorization'] = 'Bearer $token';</code></pre></div>
            <h3>Subida de imágenes</h3>
            <p>Usa <code>multipart/form-data</code>. Al actualizar, envía una solicitud POST con el campo <code>_method=PUT</code>, además de los campos del recurso y la imagen.</p>
        </section>

        <section class="guide" id="respuestas">
            <h2>Formato y códigos de respuesta</h2>
            <p>Las respuestas exitosas usan <code>{"ok": true, "data": ...}</code>. Los errores usan <code>{"ok": false, "message": "..."}</code>; los errores de validación también incluyen <code>errors</code>.</p>
            <div class="status-grid"><span><b>200</b>Correcto</span><span><b>201</b>Creado</span><span><b>401</b>Sin token</span><span><b>403</b>Sin permiso</span><span><b>422</b>Validación</span></div>
        </section>
    </main>
</div>
<script>
function copyText(text,button){navigator.clipboard.writeText(text.trim());const old=button.textContent;button.textContent='Copiado';setTimeout(()=>button.textContent=old,1200)}
document.getElementById('search').addEventListener('input',event=>{const query=event.target.value.toLowerCase();document.querySelectorAll('.endpoint').forEach(card=>card.hidden=!card.dataset.search.includes(query))});
</script>
</body>
</html>
