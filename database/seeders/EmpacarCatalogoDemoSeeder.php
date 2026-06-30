<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class EmpacarCatalogoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategorias();
        $this->seedProductos();
        $this->seedProveedores();
        $this->seedClientes();
        $this->seedMetodosPagoAdicionales();
    }

    private function seedCategorias(): void
    {
        $categorias = [
            'División PET',
            'División Corrugado',
            'División Bolsas Kraft',
            'División Reciclado',
            'División Inyección PP-PE',
            'Láminas y Termoformado',
            'Servicios y Alquileres',
        ];

        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(
                ['nombre' => $nombre],
                ['state' => 'a']
            );
        }
    }

    private function seedProductos(): void
    {
        $cat = fn (string $nombre) => Categoria::where('nombre', $nombre)->value('id');

        $productos = [
            [
                'codigo' => 'PET-BOT-500-CR',
                'nombre' => 'Botella PET 500 ml cristal',
                'descripcion' => 'Botella PET cristal de 500 ml para bebidas, envasado comercial y distribución industrial.',
                'categoria' => 'División PET',
                'precio' => 1.00,
                'precio_compra' => 0.80,
                'precio_venta' => 1.20,
                'stock' => 5000,
                'stock_minimo' => 500,
            ],
            [
                'codigo' => 'PET-BOT-2000-CR',
                'nombre' => 'Botella PET 2 litros cristal',
                'descripcion' => 'Botella PET cristal de 2 litros para bebidas, agua y productos líquidos de consumo masivo.',
                'categoria' => 'División PET',
                'precio' => 2.00,
                'precio_compra' => 1.70,
                'precio_venta' => 2.50,
                'stock' => 3000,
                'stock_minimo' => 300,
            ],
            [
                'codigo' => 'PET-PRE-28MM',
                'nombre' => 'Preforma PET 28 mm',
                'descripcion' => 'Preforma PET de 28 mm para fabricación de botellas plásticas mediante proceso de soplado.',
                'categoria' => 'División PET',
                'precio' => 0.60,
                'precio_compra' => 0.45,
                'precio_venta' => 0.75,
                'stock' => 10000,
                'stock_minimo' => 1000,
            ],
            [
                'codigo' => 'COR-CAJ-MED',
                'nombre' => 'Caja corrugada genérica mediana',
                'descripcion' => 'Caja de cartón corrugado mediana para embalaje, almacenamiento y transporte.',
                'categoria' => 'División Corrugado',
                'precio' => 5.00,
                'precio_compra' => 3.80,
                'precio_venta' => 6.50,
                'stock' => 1500,
                'stock_minimo' => 150,
            ],
            [
                'codigo' => 'COR-ARCH-STD',
                'nombre' => 'Caja de archivo corrugada',
                'descripcion' => 'Caja corrugada para archivo de documentos, oficinas, instituciones y empresas.',
                'categoria' => 'División Corrugado',
                'precio' => 7.00,
                'precio_compra' => 5.20,
                'precio_venta' => 9.00,
                'stock' => 800,
                'stock_minimo' => 100,
            ],
            [
                'codigo' => 'COR-VINO-01',
                'nombre' => 'Caja corrugada para vino',
                'descripcion' => 'Caja especial de cartón corrugado para transporte y presentación de botellas de vino.',
                'categoria' => 'División Corrugado',
                'precio' => 9.50,
                'precio_compra' => 7.50,
                'precio_venta' => 12.00,
                'stock' => 600,
                'stock_minimo' => 80,
            ],
            [
                'codigo' => 'KRF-BOL-MAR',
                'nombre' => 'Bolsa Kraft sin asa marrón',
                'descripcion' => 'Bolsa Kraft marrón sin asa para negocios gastronómicos, tiendas y comercios.',
                'categoria' => 'División Bolsas Kraft',
                'precio' => 1.30,
                'precio_compra' => 0.95,
                'precio_venta' => 1.80,
                'stock' => 2000,
                'stock_minimo' => 300,
            ],
            [
                'codigo' => 'KRF-BOL-BLA-PER',
                'nombre' => 'Bolsa Kraft blanca personalizada',
                'descripcion' => 'Bolsa Kraft blanca personalizable para comercios, restaurantes y marcas institucionales.',
                'categoria' => 'División Bolsas Kraft',
                'precio' => 2.00,
                'precio_compra' => 1.35,
                'precio_venta' => 2.50,
                'stock' => 1000,
                'stock_minimo' => 150,
            ],
            [
                'codigo' => 'REC-HOJ-PET',
                'nombre' => 'Hojuelas PET recicladas',
                'descripcion' => 'Materia prima reciclada obtenida de botellas PET para procesos de transformación plástica.',
                'categoria' => 'División Reciclado',
                'precio' => 6.00,
                'precio_compra' => 4.50,
                'precio_venta' => 7.00,
                'stock' => 2000,
                'stock_minimo' => 300,
            ],
            [
                'codigo' => 'REC-RES-PET',
                'nombre' => 'Resina PET reciclada',
                'descripcion' => 'Resina PET reciclada para fabricación de productos plásticos y artículos industriales.',
                'categoria' => 'División Reciclado',
                'precio' => 8.00,
                'precio_compra' => 6.80,
                'precio_venta' => 9.50,
                'stock' => 1200,
                'stock_minimo' => 200,
            ],
            [
                'codigo' => 'INY-ENV-PP',
                'nombre' => 'Envase PP inyectado',
                'descripcion' => 'Envase plástico inyectado en polipropileno para uso comercial, alimentario o industrial.',
                'categoria' => 'División Inyección PP-PE',
                'precio' => 1.25,
                'precio_compra' => 0.90,
                'precio_venta' => 1.60,
                'stock' => 2500,
                'stock_minimo' => 300,
            ],
            [
                'codigo' => 'TER-BAN-TRA',
                'nombre' => 'Bandeja termoformada transparente',
                'descripcion' => 'Bandeja termoformada transparente para empaque de alimentos, productos frescos o artículos comerciales.',
                'categoria' => 'Láminas y Termoformado',
                'precio' => 1.00,
                'precio_compra' => 0.75,
                'precio_venta' => 1.30,
                'stock' => 3000,
                'stock_minimo' => 400,
            ],
        ];

        foreach ($productos as $data) {
            $producto = Producto::firstOrCreate(
                ['codigo' => $data['codigo']],
                [
                    'nombre' => $data['nombre'],
                    'descripcion' => $data['descripcion'],
                    'id_categoria' => $cat($data['categoria']),
                    'fecha_ingreso' => '2026-06-28',
                    'precio' => $data['precio'],
                    'precio_compra' => $data['precio_compra'],
                    'precio_venta' => $data['precio_venta'],
                    'stock' => $data['stock'],
                    'stock_minimo' => $data['stock_minimo'],
                    'state' => 'a',
                ]
            );

            $producto->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'id_categoria' => $cat($data['categoria']),
                'precio' => $data['precio'],
                'precio_compra' => $data['precio_compra'],
                'precio_venta' => $data['precio_venta'],
                'stock_minimo' => $data['stock_minimo'],
                'state' => 'a',
            ]);
        }
    }

    private function seedProveedores(): void
    {
        $proveedores = [
            ['nombre' => 'Centro de Acopio Verde Santa Cruz', 'nit' => '4567890012', 'telefono' => '78012345', 'email' => 'acopioverde@email.com', 'direccion' => 'Zona Parque Industrial, Santa Cruz', 'contacto' => 'Lic. Mario Suárez'],
            ['nombre' => 'Proveedor Resinas Bolivia SRL', 'nit' => '3456789011', 'telefono' => '78123456', 'email' => 'ventas@resinasbolivia.com', 'direccion' => 'Av. Cristo Redentor, Santa Cruz', 'contacto' => 'Ing. Laura Méndez'],
            ['nombre' => 'Papelera Industrial Oriente', 'nit' => '2345678901', 'telefono' => '78234567', 'email' => 'contacto@papeleraoriente.com', 'direccion' => 'Parque Industrial, Santa Cruz', 'contacto' => 'Sr. Daniel Rojas'],
            ['nombre' => 'Cartones del Este SRL', 'nit' => '5678901234', 'telefono' => '78345678', 'email' => 'pedidos@cartonesdeleste.com', 'direccion' => 'Carretera a Cotoca, Santa Cruz', 'contacto' => 'Lic. Carla Gutiérrez'],
            ['nombre' => 'PlastiInsumos Bolivia', 'nit' => '6789012345', 'telefono' => '78456789', 'email' => 'comercial@plastiinsumos.com', 'direccion' => 'Av. Blanco Galindo, Cochabamba', 'contacto' => 'Ing. Pedro Salvatierra'],
        ];

        foreach ($proveedores as $data) {
            Proveedor::updateOrCreate(
                ['nit' => $data['nit']],
                $data + ['estado' => 'activo', 'state' => 'a']
            );
        }
    }

    private function seedClientes(): void
    {
        $clientes = [
            ['nombre' => 'Restaurante La Casona', 'documento' => '1020304050', 'telefono' => 76011122, 'email' => 'compras@lacasona.com', 'direccion' => 'Av. San Martín, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Supermercado Oriente', 'documento' => '2030405060', 'telefono' => 76022233, 'email' => 'administracion@superoriente.com', 'direccion' => 'Av. Banzer, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Bebidas Tropical S.A.', 'documento' => '3040506070', 'telefono' => 76033344, 'email' => 'compras@bebidastropical.com', 'direccion' => 'Parque Industrial, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Avícola Santa Cruz', 'documento' => '4050607080', 'telefono' => 76044455, 'email' => 'logistica@avicolasc.com', 'direccion' => 'Carretera al Norte, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Bodega Valle Alto', 'documento' => '5060708090', 'telefono' => 76055566, 'email' => 'pedidos@vallealto.com', 'direccion' => 'Valle de la Concepción, Tarija', 'ciudad' => 'Tarija'],
            ['nombre' => 'Panadería El Trigal', 'documento' => '6070809010', 'telefono' => 76066677, 'email' => 'compras@eltrigal.com', 'direccion' => 'Barrio Equipetrol, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Distribuidora Verde', 'documento' => '7080901020', 'telefono' => 76077788, 'email' => 'ventas@distribuidoraverde.com', 'direccion' => 'Av. Virgen de Cotoca, Santa Cruz', 'ciudad' => 'Santa Cruz'],
            ['nombre' => 'Farmacia Central', 'documento' => '8090102030', 'telefono' => 76088899, 'email' => 'compras@farmaciacentral.com', 'direccion' => 'Calle Libertad, Santa Cruz', 'ciudad' => 'Santa Cruz'],
        ];

        foreach ($clientes as $data) {
            Cliente::updateOrCreate(
                ['documento' => $data['documento']],
                $data + ['apellido' => null, 'state' => 'a']
            );
        }
    }

    private function seedMetodosPagoAdicionales(): void
    {
        $metodos = [
            ['codigo' => 'otro', 'nombre' => 'Crédito proveedor', 'es_electronico' => false, 'descripcion' => 'Compra registrada con saldo pendiente al proveedor.'],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::updateOrCreate(
                ['codigo' => $metodo['codigo']],
                [
                    'nombre' => $metodo['nombre'],
                    'es_electronico' => $metodo['es_electronico'],
                    'permite_pago_unico' => true,
                    'permite_plan_pagos' => true,
                    'descripcion' => $metodo['descripcion'],
                    'state' => 'a',
                ]
            );
        }
    }
}
