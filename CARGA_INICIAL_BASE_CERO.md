# Carga inicial del sistema desde cero

## Usuario inicial

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@empacar.local | secret |

## Condición inicial de la base de datos

Después de ejecutar:

```bash
php artisan migrate:fresh --seed
```

el sistema queda sin datos comerciales cargados. Solo existe el administrador principal.

No se crean automáticamente:

- clientes
- proveedores
- categorías
- productos
- compras
- ventas
- cuotas
- pagos
- inventario
- solicitudes

## Orden recomendado para cargar información

### 1. Empresa
Revisar los datos de la empresa genérica `EMPACAR S.A.` y reemplazarlos por los datos reales.

### 2. Categorías
Crear las categorías comerciales.

Ejemplos:

- Tecnología
- Herramientas
- Repuestos
- Muebles
- Insumos

### 3. Productos
Crear productos con:

- código
- nombre
- categoría
- precio de compra
- precio de venta
- stock mínimo

El stock puede iniciar en cero si será cargado mediante compras.

### 4. Proveedores
Crear proveedores con:

- nombre
- NIT
- teléfono
- correo
- dirección
- contacto

### 5. Compras
Registrar compras para cargar stock real al sistema.

### 6. Clientes
Crear clientes. En esta versión los clientes no generan usuarios de acceso automáticamente.

### 7. Ventas
Registrar ventas al contado o en cuotas.

### 8. Pagos
Registrar pagos de clientes, pagos de cuotas y pagos a proveedores.

### 9. Reportes
Validar cuentas por cobrar, cuentas por pagar, cuotas vencidas, stock bajo y movimientos de inventario.

### 10. Auditoría
Revisar operaciones sensibles como anulaciones y cambios importantes.
