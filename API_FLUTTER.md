# Incaland Tours — Contrato de API para Flutter

Entrega este archivo completo a la inteligencia artificial que desarrollará la aplicación Flutter. Debe tomar este documento como fuente de verdad para implementar modelos, servicios, autenticación, pantallas y control de permisos.

## Conexión

Versión de la API: `v1`

- Desde el navegador de la misma computadora: `http://localhost/incalandtours/public/api/v1`
- Desde un emulador Android en la misma computadora: `http://10.0.2.2/incalandtours/public/api/v1`
- Desde un dispositivo físico: reemplazar el host por la IP local de la computadora, por ejemplo `http://192.168.1.50/incalandtours/public/api/v1`
- En producción: reemplazar todo el origen por el dominio HTTPS definitivo y conservar `/api/v1`.

Todas las solicitudes deben enviar:

```http
Accept: application/json
```

Las solicitudes protegidas también deben enviar:

```http
Authorization: Bearer ACCESS_TOKEN
```

Los cuerpos normales usan `Content-Type: application/json`. Las imágenes usan `multipart/form-data`. No se debe establecer manualmente el boundary; `Dio` lo genera al enviar `FormData`.

## Formato de respuestas

Respuesta exitosa:

```json
{
  "ok": true,
  "message": "Mensaje opcional",
  "data": {}
}
```

Respuesta de error:

```json
{
  "ok": false,
  "message": "Descripción del error",
  "errors": {
    "campo": ["Error de validación"]
  }
}
```

Códigos esperados: `200` correcto, `201` creado, `401` token ausente/expirado, `403` sin permiso, `404` no encontrado y `422` validación.

## Autenticación

### POST `/auth/login`

Público. Inicia sesión y crea un token válido durante 30 días.

```json
{
  "email": "maria@incaland.bo",
  "password": "maria1234",
  "device_name": "Flutter Android"
}
```

La respuesta contiene:

```json
{
  "ok": true,
  "data": {
    "access_token": "TOKEN",
    "token_type": "Bearer",
    "expires_at": "2026-10-07T12:00:00-04:00",
    "staff": {
      "id": 1,
      "full_name": "María Flores",
      "email": "maria@incaland.bo",
      "phone": "+591 720 11220",
      "role": "admin",
      "branch_id": 1,
      "branch_name": "La Paz Centro"
    }
  }
}
```

Guardar `access_token` con `flutter_secure_storage`. No imprimirlo en registros ni guardarlo en texto plano.

### GET `/auth/me`

Protegido. Devuelve el empleado asociado al token.

### POST `/auth/logout`

Protegido. Revoca el token actual. Después se debe borrar el token local y volver al login.

## Catálogo y reservas públicas

### GET `/public/home`

Devuelve en una sola solicitud todo el contenido necesario para construir el home igual que la web:

- `carousel`: diapositivas activas ordenadas por `display_order`.
- `categories`: categorías disponibles.
- `packages`: paquetes activos con sus imágenes.
- `languages`: idiomas admitidos.

Cada diapositiva contiene `id`, `title`, `subtitle`, `image_path`, `image_url`, `display_order`, `active` y `updated_at`. La aplicación siempre debe mostrar imágenes usando `image_url`.

### GET `/public/carousel`

Devuelve únicamente las diapositivas activas del carrusel en el orden correcto. Puede usarse para refrescar el carrusel sin descargar nuevamente todo el home.

### GET `/public/catalog`

Devuelve `categories`, `packages` activos y `carousel`. Los paquetes y diapositivas incluyen `image_url` absoluta.

### GET `/public/packages/{id}`

Devuelve el detalle de un paquete activo.

### GET `/public/booking`

Devuelve los paquetes, idiomas y tallas necesarios para construir el formulario de reserva online.

### GET `/public/qr/{token}`

Valida el token leído de un código QR. Devuelve la sucursal, paquetes, idiomas y tallas. Un `404` significa que el QR no es válido o la sucursal está inactiva.

### POST `/public/reservations/online`

Público. Crea una reserva grupal, una salida si todavía no existe y hasta 20 viajeros.

```json
{
  "contact_name": "Ana Martínez",
  "contact_email": "ana@email.com",
  "contact_whatsapp": "+59170000000",
  "tour_package_id": 1,
  "tour_date": "2026-10-15",
  "preferred_language": "es",
  "travelers": [
    {
      "full_name": "Ana Martínez",
      "nationality": "Bolivia",
      "passport_number": "BO123456",
      "food_notes": "Vegetariana",
      "apparel_size": "M",
      "height_cm": 168,
      "hotel": "Hotel Central",
      "whatsapp": "+59170000000"
    }
  ]
}
```

`preferred_language` acepta `es`, `en`, `fr`, `pt` o `de`. La fecha no puede ser anterior al día actual.

### POST `/public/reservations/qr/{token}`

Público. Registra un viajero en la sucursal identificada por el QR.

```json
{
  "full_name": "Juan Pérez",
  "nationality": "Perú",
  "passport_number": "PE998877",
  "food_notes": "Vegetariano",
  "apparel_size": "L",
  "height_cm": 178,
  "hotel": "Hotel Central",
  "whatsapp": "+51999000111",
  "preferred_language": "es"
}
```

## Panel y operaciones internas

Todos estos endpoints requieren token.

### GET `/dashboard`

Devuelve `staff`, `summary`, `recent_reservations` y `next_departure`. El resumen incluye reservas pendientes, reservas web, salidas abiertas y equipamiento disponible/total.

### GET `/reservations`

Lista reservas visibles para el usuario. Filtros opcionales:

- `status`: `pending_package`, `pending_equipment` o `confirmed`.
- `source`: `qr` u `online`.
- `search`: nombre o pasaporte.

Ejemplo: `/reservations?status=pending_equipment&search=Ana`

Los recepcionistas solo pueden ver registros de su sucursal y reservas online sin sucursal.

### GET `/reservations/{id}`

Devuelve turista, reserva, paquete, sucursal, referencia de reserva grupal y equipamiento asignado.

### PUT `/reservations/{id}/complete`

Administrador o recepcionista autorizado. Completa la reserva y agrega el turista a una salida.

```json
{
  "tour_package_id": 1,
  "tour_date": "2026-10-15",
  "equipment_ids": [2, 3],
  "notes": "Talla verificada"
}
```

### GET `/departures`

Lista salidas. Filtros opcionales:

- `date`: fecha `YYYY-MM-DD`.
- `package_id`: identificador del paquete.
- `status`: `open` o `closed`.

### GET `/departures/{id}`

Devuelve fecha, paquete, ubicación, estado, guía, conductor, incluidos, no incluidos, observaciones y viajeros visibles para el rol.

### PUT `/departures/{id}/close`

Solo administrador.

```json
{
  "includes": "Transporte y alimentación",
  "excludes": "Propinas",
  "guide": "Julio Mamani",
  "driver": "Óscar Rojas",
  "observations": "Sin novedades"
}
```

## Catálogo interno

Tipos aceptados en `{type}`:

- `branches`
- `categories`
- `packages`
- `equipment`
- `lodgings`
- `employees`
- `slides`

### GET `/catalog/{type}`

Protegido. Lista un tipo de recurso. `employees` y `slides` solo pueden ser consultados por administradores. Nunca se devuelven contraseñas ni tokens QR desde estos listados.

### GET `/catalog/{type}/{id}`

Protegido. Devuelve un recurso específico.

### POST `/catalog/{type}`

Solo administrador. Crea un recurso.

Campos por tipo:

- `branches`: `name`, `city`, `address`, `phone` opcional.
- `categories`: `name`, `color` opcional.
- `packages`: `name`, `tour_category_id`, `location`, `duration_days`, `price`, `description` opcional, `image` opcional.
- `equipment`: `name`, `category`, `size` opcional, `stock`, `condition` opcional, `image` opcional.
- `lodgings`: `name`, `city`, `address`, `rooms`, `available_rooms`, `image` opcional.
- `employees`: `full_name`, `email`, `phone` opcional, `role`, `branch_id`, `password` con mínimo 8 caracteres.
- `slides`: `title`, `subtitle` opcional, `display_order`, `active` (`0` o `1`) e `image` obligatoria.

Las imágenes admitidas son JPG, JPEG, PNG y WebP, con un máximo de 8 MB. Esto aplica a `packages`, `equipment`, `lodgings` y `slides`.

### PUT `/catalog/{type}/{id}`

Solo administrador. Actualiza un recurso usando los mismos campos. La contraseña del empleado es opcional al editar.

Para actualizar con imagen mediante multipart, enviar POST y agregar:

```text
_method=PUT
```

Si se envía una nueva imagen, la API reemplaza el archivo anterior y devuelve el nuevo `image_path` e `image_url`. En una actualización de `slides`, la imagen es opcional; si no se envía, se conserva la existente.

Ejemplo Flutter para crear una diapositiva:

```dart
final form = FormData.fromMap({
  'title': 'Bolivia inolvidable',
  'subtitle': 'Una experiencia diseñada por expertos locales.',
  'display_order': 4,
  'active': 1,
  'image': await MultipartFile.fromFile(image.path, filename: 'portada.webp'),
});

final response = await dio.post('/catalog/slides', data: form);
```

Ejemplo Flutter para actualizar un recurso con una nueva imagen:

```dart
final form = FormData.fromMap({
  '_method': 'PUT',
  'title': 'Bolivia inolvidable',
  'subtitle': 'Contenido actualizado desde la aplicación.',
  'display_order': 2,
  'active': 1,
  'image': await MultipartFile.fromFile(image.path),
});

await dio.post('/catalog/slides/$slideId', data: form);
```

### DELETE `/catalog/slides/{id}`

Solo administrador. Elimina la diapositiva y, cuando corresponde, también elimina del almacenamiento el archivo cargado.

### GET `/branches/{id}/qr`

Solo administrador. Devuelve:

- `qr_token`
- `qr_payload`
- `registration_url`
- `api_form_url`
- `api_submit_url`
- `qr_image_url`

La aplicación puede mostrar directamente `qr_image_url` o generar un QR local usando `qr_payload`.

## Permisos

Administrador:

- Acceso a todas las reservas y salidas.
- Puede cerrar salidas.
- Puede crear y actualizar catálogo, sucursales y empleados.
- Puede listar, crear, editar, ocultar y eliminar diapositivas del carrusel.
- Puede cargar o reemplazar imágenes de paquetes, equipamiento, hospedajes y carrusel.
- Puede obtener los datos QR de una sucursal.

Recepcionista:

- Ve reservas de su sucursal y reservas online.
- Puede completar reservas autorizadas.
- Puede consultar salidas, paquetes, equipamiento, hospedajes, categorías y sucursales.
- No puede consultar empleados, modificar el catálogo ni cerrar salidas.

## Requisitos para la implementación Flutter

La aplicación debe:

1. Usar `Dio` o un cliente HTTP equivalente.
2. Centralizar la URL base en una configuración por ambiente.
3. Crear un interceptor que agregue `Accept: application/json` y el token Bearer.
4. Guardar el token con `flutter_secure_storage`.
5. Ante cualquier `401`, borrar el token y enviar al usuario al login.
6. Mostrar los mensajes de `422` junto al campo correspondiente.
7. Crear modelos con valores opcionales porque varios datos pueden ser `null`.
8. Ocultar en la interfaz las acciones no permitidas para recepcionistas, aunque el backend también las bloquea.
9. Implementar lectura QR usando el valor capturado como `{token}` en `/public/qr/{token}`.
10. No insertar credenciales, tokens ni la URL de desarrollo directamente en el código de producción.
11. Construir el home consumiendo `/public/home`, respetando `display_order` y mostrando solamente el carrusel recibido por la API.
12. Implementar selección de imágenes desde cámara o galería para paquetes, equipamiento, hospedajes y diapositivas.
13. Comprimir imágenes grandes antes de subirlas, conservar una vista previa local y mostrar el progreso de carga.
14. Usar siempre `image_url` para visualizar archivos remotos; `image_path` es solamente una referencia interna.
15. Permitir al administrador cambiar la visibilidad y orden de las diapositivas, además de confirmar antes de eliminarlas.

## Credenciales locales de demostración

- Administradora: `maria@incaland.bo` / `maria1234`
- Recepción La Paz: `daniel@incaland.bo` / `daniel1234`
- Recepción Uyuni: `camila@incaland.bo` / `camila1234`

Estas credenciales son solo para desarrollo local y deben cambiarse antes de publicar el sistema.
