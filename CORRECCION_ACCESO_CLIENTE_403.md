# Corrección de acceso para usuario Cliente

## Problema detectado
Al iniciar sesión con `cliente@empacar.local`, Laravel redirigía siempre a `/dashboard`.

La ruta `/dashboard` está protegida con el privilegio `Reportes: leer`, pero el rol `Cliente` no debe tener acceso a reportes administrativos. Por eso el sistema mostraba error 403: "No tienes permisos para realizar esta acción".

## Corrección aplicada

1. Se modificó la redirección posterior al login en:
   - `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

2. Ahora el sistema detecta los privilegios del usuario y lo envía a la primera ruta autorizada:
   - Administrador: `dashboard`
   - Usuario con Reportes: `dashboard`
   - Cliente: `venta.index`
   - Otros roles: primera ruta permitida según su matriz de acceso

3. Se agregó `homeRoute` al middleware de Inertia:
   - `app/Http/Middleware/HandleInertiaRequests.php`

4. Se corrigieron los enlaces del logo y "Panel principal" en:
   - `resources/js/Layouts/AppLayout.vue`

5. Para evitar otro 403, el buscador global del encabezado solo se muestra a usuarios con permiso de Reportes o Administrador.

6. Se reforzó la privacidad del rol Cliente:
   - En `VentaController`, el cliente solo ve sus propias ventas.
   - En `PlanPagoController`, el cliente solo ve sus propios planes de pago.
   - En `PagoController`, el cliente solo ve sus propios pagos.
   - El cliente no puede registrar pagos sobre ventas de otro cliente.

## Cómo probar

1. Ejecutar:

```bash
php artisan optimize:clear
npm run dev
```

2. Iniciar sesión con:

```txt
Correo: cliente@empacar.local
Contraseña: secret
```

3. Resultado esperado:

El sistema debe ingresar al módulo de ventas del cliente, sin error 403.
