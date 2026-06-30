# Seeders de datos demo EMPACAR

Se agregaron dos seeders para poblar el sistema con datos de prueba coherentes con EMPACAR S.A.

## Archivos agregados

- `database/seeders/EmpacarCatalogoDemoSeeder.php`
- `database/seeders/EmpacarOperacionesDemoSeeder.php`

También se actualizó:

- `database/seeders/DatabaseSeeder.php`

## Qué datos carga

### Catálogo

- Categorías:
  - División PET
  - División Corrugado
  - División Bolsas Kraft
  - División Reciclado
  - División Inyección PP-PE
  - Láminas y Termoformado
  - Servicios y Alquileres

- Productos:
  - Botella PET 500 ml cristal
  - Botella PET 2 litros cristal
  - Preforma PET 28 mm
  - Caja corrugada genérica mediana
  - Caja de archivo corrugada
  - Caja corrugada para vino
  - Bolsa Kraft sin asa marrón
  - Bolsa Kraft blanca personalizada
  - Hojuelas PET recicladas
  - Resina PET reciclada
  - Envase PP inyectado
  - Bandeja termoformada transparente

- Proveedores:
  - Centro de Acopio Verde Santa Cruz
  - Proveedor Resinas Bolivia SRL
  - Papelera Industrial Oriente
  - Cartones del Este SRL
  - PlastiInsumos Bolivia

- Clientes:
  - Restaurante La Casona
  - Supermercado Oriente
  - Bebidas Tropical S.A.
  - Avícola Santa Cruz
  - Bodega Valle Alto
  - Panadería El Trigal
  - Distribuidora Verde
  - Farmacia Central

### Operaciones demo

- Compras aprobadas con actualización de stock.
- Compra rechazada con motivo administrativo.
- Ventas al contado.
- Venta con PagoFácil simulada.
- Ventas con planes de cuotas.
- Pagos iniciales.
- Pagos de cuotas.
- Movimientos manuales de inventario.

## Forma recomendada de ejecución

Para cargar el sistema completo desde cero:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
npm run dev
```

Esto borra la base de datos y la vuelve a crear con usuarios, roles, privilegios, métodos de pago y datos demo EMPACAR.

## Usuarios demo

- Administrador: `admin@empacar.local` / `secret`
- Vendedor: `vendedor@empacar.local` / `secret`
- Cajero: `cajero@empacar.local` / `secret`
- Inventario: `inventario@empacar.local` / `secret`
- Cliente: `cliente@empacar.local` / `secret`

## Precaución

Si ya tienes información importante en la base de datos, no uses `migrate:fresh --seed`, porque borra todos los datos. En ese caso ejecuta solo:

```bash
php artisan db:seed --class=EmpacarCatalogoDemoSeeder
php artisan db:seed --class=EmpacarOperacionesDemoSeeder
```

Los seeders intentan evitar duplicados usando códigos, referencias, NIT y documentos, pero para una defensa académica se recomienda usarlos sobre base limpia.
