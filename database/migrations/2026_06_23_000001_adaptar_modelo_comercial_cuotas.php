<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'id_departamento')) {
                $table->unsignedBigInteger('id_departamento')->nullable();
                $table->foreign('id_departamento')->references('id')->on('departamentos')->nullOnDelete();
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            if (! Schema::hasColumn('clientes', 'apellido')) {
                $table->string('apellido')->nullable();
            }
            if (! Schema::hasColumn('clientes', 'documento')) {
                $table->string('documento')->nullable()->index();
            }
            if (! Schema::hasColumn('clientes', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('clientes', 'ciudad')) {
                $table->string('ciudad')->nullable();
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'codigo')) {
                $table->string('codigo')->nullable()->unique();
            }
            if (! Schema::hasColumn('productos', 'precio_compra')) {
                $table->decimal('precio_compra', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('productos', 'precio_venta')) {
                $table->decimal('precio_venta', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('productos', 'stock_minimo')) {
                $table->integer('stock_minimo')->default(0);
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('ventas', 'descuento')) {
                $table->decimal('descuento', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('ventas', 'monto_pagado')) {
                $table->decimal('monto_pagado', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('ventas', 'saldo')) {
                $table->decimal('saldo', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('ventas', 'tipo_pago')) {
                $table->string('tipo_pago')->default('contado');
            }
            if (! Schema::hasColumn('ventas', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
        });

        Schema::table('detalle_venta', function (Blueprint $table) {
            if (! Schema::hasColumn('detalle_venta', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }
        });

        Schema::table('plan_pago', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_pago', 'monto_inicial')) {
                $table->decimal('monto_inicial', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('plan_pago', 'saldo_financiado')) {
                $table->decimal('saldo_financiado', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('plan_pago', 'frecuencia')) {
                $table->string('frecuencia')->default('mensual');
            }
            if (! Schema::hasColumn('plan_pago', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
        });

        Schema::table('pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos', 'id_cliente')) {
                $table->unsignedBigInteger('id_cliente')->nullable();
                $table->foreign('id_cliente')->references('id')->on('clientes')->nullOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'id_cuota')) {
                $table->unsignedBigInteger('id_cuota')->nullable();
                $table->foreign('id_cuota')->references('id')->on('cuotas')->nullOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'referencia')) {
                $table->string('referencia')->nullable();
            }
            if (! Schema::hasColumn('pagos', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->index();
            }
            if (! Schema::hasColumn('pagos', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nit')->nullable()->index();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('contacto')->nullable();
            $table->string('estado')->default('activo');
            $table->string('state')->default('a');
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_proveedor');
            $table->unsignedBigInteger('id_usuario');
            $table->date('fecha_compra');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->string('estado')->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_proveedor')->references('id')->on('proveedores')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('detalle_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_compra');
            $table->unsignedBigInteger('id_producto');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_compra')->references('id')->on('compras')->cascadeOnDelete();
            $table->foreign('id_producto')->references('id')->on('productos')->restrictOnDelete();
        });

        Schema::create('pago_proveedor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_proveedor');
            $table->unsignedBigInteger('id_compra')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->decimal('monto', 12, 2);
            $table->date('fecha_pago');
            $table->string('metodo_pago');
            $table->string('referencia')->nullable();
            $table->string('estado')->default('confirmado');
            $table->text('observaciones')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_proveedor')->references('id')->on('proveedores')->restrictOnDelete();
            $table->foreign('id_compra')->references('id')->on('compras')->nullOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('tipo_accion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();
        });

        Schema::create('accion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tipo_accion')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado_actual')->nullable();
            $table->string('estado_siguiente')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_tipo_accion')->references('id')->on('tipo_accion')->nullOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_departamento')->nullable();
            $table->text('descripcion');
            $table->text('justificacion')->nullable();
            $table->date('fecha_solicitud');
            $table->date('fecha_requerida')->nullable();
            $table->string('estado')->default('pendiente');
            $table->string('moneda')->default('BOB');
            $table->text('observaciones')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('id_departamento')->references('id')->on('departamentos')->nullOnDelete();
        });

        Schema::create('detalle_solicitud', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_solicitud');
            $table->unsignedBigInteger('id_producto')->nullable();
            $table->unsignedBigInteger('id_articulo')->nullable();
            $table->string('nombre_articulo')->nullable();
            $table->integer('cantidad');
            $table->decimal('precio_estimado', 12, 2)->default(0);
            $table->decimal('importe', 12, 2)->default(0);
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_solicitud')->references('id')->on('solicitudes')->cascadeOnDelete();
            $table->foreign('id_producto')->references('id')->on('productos')->nullOnDelete();
        });

        Schema::create('pagofacil_transacciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_venta')->nullable();
            $table->unsignedBigInteger('id_cuota')->nullable();
            $table->unsignedBigInteger('id_pago')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->string('payment_number')->nullable()->index();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('estado')->default('generado');
            $table->text('qr_url')->nullable();
            $table->longText('qr_base64')->nullable();
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->json('webhook_json')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamps();

            $table->foreign('id_venta')->references('id')->on('ventas')->nullOnDelete();
            $table->foreign('id_cuota')->references('id')->on('cuotas')->nullOnDelete();
            $table->foreign('id_pago')->references('id')->on('pagos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagofacil_transacciones');
        Schema::dropIfExists('detalle_solicitud');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('accion');
        Schema::dropIfExists('tipo_accion');
        Schema::dropIfExists('pago_proveedor');
        Schema::dropIfExists('detalle_compra');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');

        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'id_cuota')) {
                $table->dropForeign(['id_cuota']);
                $table->dropColumn('id_cuota');
            }
            if (Schema::hasColumn('pagos', 'id_cliente')) {
                $table->dropForeign(['id_cliente']);
                $table->dropColumn('id_cliente');
            }
            foreach (['referencia', 'transaction_id', 'observaciones'] as $column) {
                if (Schema::hasColumn('pagos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_departamento')) {
                $table->dropForeign(['id_departamento']);
                $table->dropColumn('id_departamento');
            }
        });

        Schema::dropIfExists('departamentos');
    }
};
