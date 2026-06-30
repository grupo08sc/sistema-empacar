# Cumplimiento de requisitos académicos incorporado

Este documento resume la segunda ronda de correcciones aplicada al sistema comercial de ventas, cuotas, inventario y pagos electrónicos.

## 1. Diseño y navegación

- Se mantiene la línea base visual con AdminLTE + Inertia + Vue.
- Se reorganizó el menú lateral para que se alimente desde permisos registrados en base de datos.
- Se mantuvo buscador en el encabezado principal.
- Se agregó pie de página con contador automático de visitas por página.

## 2. Roles de acceso y menú dinámico

Se agregaron roles iniciales de negocio en `RoleSeeder`:

- Vendedor
- Cajero
- Encargado de Inventario
- Cliente

El rol Administrador permanece, pero no se cuenta como rol de negocio.

Se agregaron privilegios iniciales por rol en `PrivilegioSeeder` y se genera el menú desde la matriz de privilegios activa del usuario.

## 3. MVC-MVVM

Se conserva la arquitectura Laravel + Inertia + Vue:

- Modelos Eloquent.
- Controladores Laravel.
- Servicios para operaciones de negocio.
- Vistas Vue renderizadas por Inertia.

## 4. Control de acceso, matriz y bitácora

Se reforzó:

- Middleware de privilegios por módulo y acción.
- Matriz de acceso por roles y funcionalidades.
- Bitácora de:
  - login aceptado;
  - login fallido;
  - login rechazado por cuenta inactiva;
  - logout;
  - recursos visitados;
  - IP;
  - navegador;
  - fecha y hora.

Archivos principales:

- `app/Services/AuditoriaService.php`
- `app/Http/Middleware/RegistrarAccesoPagina.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Requests/Auth/LoginRequest.php`

## 5. Estilo único, temas y accesibilidad

En `resources/js/Layouts/AppLayout.vue` se incorporaron:

- Tema Niños.
- Tema Jóvenes.
- Tema Adultos.
- Modo Día.
- Modo Noche.
- Modo Automático Día/Noche según la hora del cliente.
- Botón A+ para aumentar letra.
- Botón A- para disminuir letra.
- Modo de alto contraste.

## 6. Validaciones en español

Se reforzaron validaciones en:

- búsqueda global;
- selección de cuotas;
- motivos de anulación;
- catálogo de métodos de pago;
- estilos visuales;
- planes de pago.

Los mensajes críticos agregados están en español.

## 7. Contador por página

Se creó middleware automático:

- `app/Http/Middleware/RegistrarAccesoPagina.php`

Este middleware cuenta visitas por ruta/página, registra el módulo actual en sesión y alimenta el pie de página.

También se actualizó:

- `app/Models/Contador.php`
- `database/seeders/ContadorSeeder.php`

## 8. Estadísticas del negocio y acceso

El sistema conserva estadísticas comerciales y financieras y ahora también muestra:

- contador general por página;
- recursos más accedidos desde la bitácora.

Archivos principales:

- `app/Http/Controllers/ReporteController.php`
- `resources/js/Pages/Estadisticas/Resultado.vue`

## 9. Búsqueda de información del negocio

Se mantiene el buscador en el encabezado principal y se reforzó validación del campo de búsqueda.

Ruta:

- `reportes.buscar`

Controlador:

- `ReporteController@buscador`

## 10. Pagos electrónicos, pago único y planes de pago

Se mantiene:

- PagoFácil QR.
- Pago único de venta.
- Pago de cuotas.
- Planes de pago.
- Webhook reforzado.

Además se agregó catálogo administrativo de métodos de pago:

- Tabla: `metodos_pago`
- Modelo: `MetodoPago`
- Controlador: `MetodoPagoController`
- Vista: `resources/js/Pages/MetodoPago/Index.vue`
- Seeder: `MetodoPagoSeeder`

## Usuarios iniciales de prueba

Todos usan contraseña inicial `secret` para entorno académico/desarrollo:

- `admin@empacar.local` - Administrador
- `vendedor@empacar.local` - Vendedor
- `cajero@empacar.local` - Cajero
- `inventario@empacar.local` - Encargado de Inventario
- `cliente@empacar.local` - Cliente

> En producción se debe cambiar la contraseña inicial.
