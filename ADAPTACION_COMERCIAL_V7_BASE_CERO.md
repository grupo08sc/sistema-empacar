# Adaptación comercial V7 - Base cero

## Objetivo
Esta versión deja el sistema listo para iniciar carga manual desde cero.

## Seeder inicial
Al ejecutar:

```bash
php artisan migrate:fresh --seed
```

solo se crean datos mínimos de arranque:

- 1 empresa genérica: `EMPACAR S.A.`
- 1 rol habilitado: `Administrador`
- 1 usuario administrador
- privilegios completos para el rol Administrador
- contadores técnicos del sistema

## Usuario inicial

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@empacar.local | secret |

## Datos que ya no se cargan automáticamente

No se crean datos de ejemplo para:

- clientes
- productos
- categorías
- proveedores
- compras
- ventas
- cuotas
- pagos
- inventario
- departamentos
- solicitudes

## Orden recomendado para carga inicial

1. Revisar o editar datos de la empresa.
2. Crear categorías.
3. Crear productos.
4. Crear proveedores.
5. Registrar compras para cargar stock.
6. Crear clientes.
7. Registrar ventas al contado.
8. Registrar ventas en cuotas.
9. Registrar pagos de cuotas.
10. Revisar reportes y auditoría.

## Nota técnica
Los módulos y tablas del sistema comercial se mantienen disponibles, pero inician vacíos. Esta versión es adecuada para pruebas desde cero o carga real controlada.
