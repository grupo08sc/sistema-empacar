# EMPACAR S.A. - EMPACAR S.A. con Ventas en Cuotas

Proyecto Laravel 11 + Inertia + Vue 3 adaptado para gestión comercial-administrativa.

## Estado de esta versión

Esta es una versión **base cero**. El sistema inicia limpio para que el administrador cargue la información desde el panel.

Al ejecutar:

```bash
php artisan migrate:fresh --seed
```

solo se crean:

- 1 empresa genérica.
- 1 rol: Administrador.
- 1 usuario administrador.
- privilegios completos para el administrador.
- contadores técnicos del sistema.

No se cargan clientes, productos, proveedores, compras, ventas, cuotas, pagos ni datos de inventario de ejemplo.

## Módulos principales

- Usuarios, roles y privilegios.
- Clientes.
- Productos, categorías e inventario.
- Proveedores, compras y pagos a proveedores.
- Ventas al contado, crédito y modalidad mixta.
- Planes de pago y cuotas.
- Pagos de clientes y PagoFácil.
- Solicitudes internas.
- Reportes financieros.
- Auditoría de acciones críticas.

## Módulos eliminados del proyecto anterior

- Citas.
- Servicios de estética.
- Promociones del modelo anterior.
- Roles Médico y Secretaría.

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Usuario inicial

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@empacar.local | secret |

## Orden recomendado para cargar datos

1. Revisar o editar datos de empresa.
2. Crear categorías.
3. Crear productos.
4. Crear proveedores.
5. Registrar compras para cargar stock.
6. Crear clientes.
7. Registrar ventas al contado.
8. Registrar ventas en cuotas.
9. Registrar pagos de cuotas.
10. Revisar reportes y auditoría.
