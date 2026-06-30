<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (! Schema::hasColumn('compras', 'estado_aprobacion')) {
                $table->string('estado_aprobacion')->default('aprobada')->after('estado')->index();
            }
            if (! Schema::hasColumn('compras', 'solicitado_por')) {
                $table->unsignedBigInteger('solicitado_por')->nullable()->after('id_usuario');
            }
            if (! Schema::hasColumn('compras', 'aprobado_por')) {
                $table->unsignedBigInteger('aprobado_por')->nullable()->after('solicitado_por');
            }
            if (! Schema::hasColumn('compras', 'fecha_solicitud')) {
                $table->dateTime('fecha_solicitud')->nullable()->after('fecha_compra');
            }
            if (! Schema::hasColumn('compras', 'fecha_aprobacion')) {
                $table->dateTime('fecha_aprobacion')->nullable()->after('fecha_solicitud');
            }
            if (! Schema::hasColumn('compras', 'metodo_pago_propuesto')) {
                $table->string('metodo_pago_propuesto')->nullable()->after('observaciones');
            }
            if (! Schema::hasColumn('compras', 'referencia_pago_propuesto')) {
                $table->string('referencia_pago_propuesto')->nullable()->after('metodo_pago_propuesto');
            }
            if (! Schema::hasColumn('compras', 'motivo_rechazo')) {
                $table->text('motivo_rechazo')->nullable()->after('referencia_pago_propuesto');
            }
            if (! Schema::hasColumn('compras', 'observacion_aprobacion')) {
                $table->text('observacion_aprobacion')->nullable()->after('motivo_rechazo');
            }
            if (! Schema::hasColumn('compras', 'stock_aplicado')) {
                $table->boolean('stock_aplicado')->default(false)->after('observacion_aprobacion')->index();
            }
        });

        // Las compras existentes ya fueron ejecutadas por la versión anterior del sistema.
        // Por eso se migran como aprobadas y con stock aplicado para no duplicar inventario.
        DB::table('compras')->whereNull('solicitado_por')->update([
            'estado_aprobacion' => 'aprobada',
            'solicitado_por' => DB::raw('id_usuario'),
            'aprobado_por' => DB::raw('id_usuario'),
            'fecha_solicitud' => DB::raw('created_at'),
            'fecha_aprobacion' => DB::raw('updated_at'),
            'stock_aplicado' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $columns = [
                'estado_aprobacion',
                'solicitado_por',
                'aprobado_por',
                'fecha_solicitud',
                'fecha_aprobacion',
                'metodo_pago_propuesto',
                'referencia_pago_propuesto',
                'motivo_rechazo',
                'observacion_aprobacion',
                'stock_aplicado',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('compras', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
