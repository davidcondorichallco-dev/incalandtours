# Prompt actualizado para consumir la API de Incaland Tours

Actúa como un desarrollador senior de Flutter y actualiza mi aplicación móvil para consumir completamente la API REST de Incaland Tours. Usa `API_FLUTTER.md` como contrato y fuente de verdad. No uses datos simulados ni dupliques reglas del backend.

La URL base de desarrollo para Android Emulator es:

`http://10.0.2.2/incalandtours/public/api/v1`

Para un dispositivo físico, reemplaza `10.0.2.2` por la IP local de la computadora. Centraliza esta URL en configuración por ambiente.

## Arquitectura y conexión

- Usa Dio y crea un `ApiClient` centralizado.
- Envía `Accept: application/json` en todas las solicitudes.
- Guarda `access_token` con `flutter_secure_storage` y agrega `Authorization: Bearer TOKEN` mediante un interceptor.
- Si la API responde `401`, elimina la sesión local y regresa al login.
- Modela correctamente respuestas exitosas, errores `403`, `404`, `422` y errores de conexión.
- Muestra los mensajes de validación de `errors` junto a cada campo.
- No registres contraseñas, tokens ni cuerpos sensibles.
- Usa modelos null-safe y separa DTO, repositorio, estado y presentación de acuerdo con la arquitectura existente.

## Home público y carrusel

- Construye el home con `GET /public/home`.
- La respuesta contiene `carousel`, `categories`, `packages` y `languages`.
- También permite refrescar solamente el carrusel con `GET /public/carousel`.
- Renderiza las diapositivas según `display_order`, usando siempre `image_url`.
- El carrusel debe cambiar automáticamente, permitir navegación manual, oscurecer ligeramente la fotografía y aplicar un zoom suave, igual que la web.
- Muestra título, subtítulo y estados de carga, vacío y error sin bloquear el resto del home.
- El detalle de un paquete se obtiene con `GET /public/packages/{id}`.
- Implementa reservas online y registros QR usando los endpoints descritos en `API_FLUTTER.md`.

## Autenticación, panel y operaciones

- Implementa login con `POST /auth/login`, restauración de sesión con `GET /auth/me` y logout con `POST /auth/logout`.
- Consume `GET /dashboard` para el resumen según el rol.
- Implementa listado, filtros, detalle y finalización de reservas con `/reservations`.
- Implementa listado, filtros y detalle de salidas con `/departures`.
- Solo un administrador puede cerrar una salida mediante `PUT /departures/{id}/close`.
- Un recepcionista solamente puede ver y gestionar información autorizada para su sucursal.

## Catálogos y permisos

- Implementa `GET /catalog/{type}` y `GET /catalog/{type}/{id}` para `branches`, `categories`, `packages`, `equipment`, `lodgings`, `employees` y `slides`.
- `employees` y `slides` requieren rol administrador.
- El administrador puede crear y actualizar recursos con `POST /catalog/{type}` y `PUT /catalog/{type}/{id}`.
- Para actualizar con archivos usa `POST /catalog/{type}/{id}` con `_method=PUT`.
- El carrusel debe tener una pantalla administrativa que permita listar, crear, editar, ordenar, activar/desactivar y eliminar diapositivas.
- Elimina una diapositiva con `DELETE /catalog/slides/{id}` y solicita confirmación antes de hacerlo.
- No muestres acciones administrativas a recepcionistas; el backend igualmente debe seguir siendo la autoridad final.

## Imágenes

- Permite elegir imagen desde cámara o galería para `packages`, `equipment`, `lodgings` y `slides`.
- Envía imágenes con `FormData` y `MultipartFile`; no configures manualmente el boundary.
- Formatos permitidos: JPG, JPEG, PNG y WebP. Tamaño máximo: 8 MB.
- En la creación de `slides`, la imagen es obligatoria. En la edición es opcional y debe conservarse si no se selecciona otra.
- Comprime imágenes grandes antes de enviarlas, muestra vista previa y progreso de subida.
- Después de guardar, reemplaza el estado local usando el objeto devuelto por la API y visualiza siempre `image_url`, nunca construyas manualmente la URL desde `image_path`.

Ejemplo para crear una diapositiva:

```dart
final formData = FormData.fromMap({
  'title': title,
  'subtitle': subtitle,
  'display_order': displayOrder,
  'active': isActive ? 1 : 0,
  'image': await MultipartFile.fromFile(
    selectedImage.path,
    filename: selectedImage.name,
  ),
});

final response = await dio.post('/catalog/slides', data: formData);
```

Ejemplo para actualizar una diapositiva:

```dart
final fields = <String, dynamic>{
  '_method': 'PUT',
  'title': title,
  'subtitle': subtitle,
  'display_order': displayOrder,
  'active': isActive ? 1 : 0,
};

if (selectedImage != null) {
  fields['image'] = await MultipartFile.fromFile(
    selectedImage.path,
    filename: selectedImage.name,
  );
}

await dio.post('/catalog/slides/$slideId', data: FormData.fromMap(fields));
```

## Entrega esperada

- Implementa el código completo, no solamente ejemplos.
- Conserva el diseño y arquitectura que ya tenga la aplicación.
- Incluye estados de carga, actualización, vacío, error, reintento y progreso de subida.
- Actualiza modelos, servicios, repositorios, providers/blocs/controllers, rutas y pantallas necesarias.
- Añade pruebas para autenticación, permisos, parseo del home, carrusel, carga multipart, reemplazo de imagen y eliminación de diapositivas.
- Al finalizar, entrega una lista breve de archivos modificados, decisiones importantes y cualquier configuración que deba cambiar para dispositivo físico o producción.
