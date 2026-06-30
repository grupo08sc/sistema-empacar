<script setup>
import { reactive } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NoPermiso from '@/Components/NoPermiso.vue';

const props = defineProps({
    filtros: Object,
    resumen: Object,
    cuentasPorCobrar: Array,
    cuentasPorPagar: Array,
    cuotasVencidas: Array,
    stockBajo: Array,
    movimientosInventario: Array,
    pagosRecientes: Array,
    pagosProveedorRecientes: Array,
});

const page = usePage();
const can = (funcionalidad) => page.props.auth.privilegios?.[funcionalidad]?.leer;

const filtrosForm = reactive({
    fecha_inicio: props.filtros?.fecha_inicio ?? '',
    fecha_fin: props.filtros?.fecha_fin ?? '',
});

const filtrar = () => {
    router.get(route('reportes.financiero'), filtrosForm, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const limpiar = () => {
    filtrosForm.fecha_inicio = '';
    filtrosForm.fecha_fin = '';
    router.get(route('reportes.financiero'), {}, {
        preserveScroll: true,
        replace: true,
    });
};

const exportarCsv = () => {
    window.location.href = route('reportes.financiero.exportar', {
        fecha_inicio: filtrosForm.fecha_inicio,
        fecha_fin: filtrosForm.fecha_fin,
    });
};

const money = (value) => {
    const number = Number(value ?? 0);
    return `Bs ${number.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const number = (value) => Number(value ?? 0).toLocaleString('es-BO');

const estadoClass = (estado) => {
    const normalized = String(estado ?? '').toLowerCase();
    if (['pagado', 'confirmado', 'activo'].includes(normalized)) return 'badge badge-success';
    if (['parcial', 'en_curso'].includes(normalized)) return 'badge badge-info';
    if (['pendiente', 'vencida', 'vencido'].includes(normalized)) return 'badge badge-warning';
    if (['anulado', 'rechazado'].includes(normalized)) return 'badge badge-danger';
    return 'badge badge-secondary';
};
</script>

<template>
    <Head title="Control financiero" />

    <AppLayout>
        <section class="content" v-if="can('Reportes')">
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card card-outline card-primary">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="card-title mb-0">
                                        <i class="fas fa-balance-scale mr-2"></i>
                                        Control financiero y operativo
                                    </h3>
                                    <p class="text-muted mb-0 mt-1">
                                        Cuentas por cobrar, cuentas por pagar, cuotas vencidas, stock bajo y movimientos recientes.
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <form class="row align-items-end" @submit.prevent="filtrar">
                                    <div class="col-md-3">
                                        <label>Fecha inicial</label>
                                        <input v-model="filtrosForm.fecha_inicio" type="date" class="form-control" />
                                    </div>
                                    <div class="col-md-3">
                                        <label>Fecha final</label>
                                        <input v-model="filtrosForm.fecha_fin" type="date" class="form-control" />
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-search mr-1"></i> Filtrar
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary mr-2" @click="limpiar">
                                            Limpiar
                                        </button>
                                        <button type="button" class="btn btn-outline-success" @click="exportarCsv">
                                            <i class="fas fa-file-csv mr-1"></i> Exportar CSV
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h4>{{ money(resumen?.ventas_recaudado) }}</h4>
                                <p>Recaudado en ventas</p>
                            </div>
                            <div class="icon"><i class="fas fa-cash-register"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h4>{{ money(resumen?.cuentas_por_cobrar) }}</h4>
                                <p>Cuentas por cobrar</p>
                            </div>
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h4>{{ money(resumen?.cuotas_vencidas_total) }}</h4>
                                <p>Cuotas vencidas</p>
                            </div>
                            <div class="icon"><i class="fas fa-calendar-times"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h4>{{ money(resumen?.cuentas_por_pagar) }}</h4>
                                <p>Cuentas por pagar</p>
                            </div>
                            <div class="icon"><i class="fas fa-truck-loading"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ventas del periodo</span>
                                <span class="info-box-number">{{ money(resumen?.ventas_total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-boxes"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Compras del periodo</span>
                                <span class="info-box-number">{{ money(resumen?.compras_total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-money-check-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pagado a proveedores</span>
                                <span class="info-box-number">{{ money(resumen?.proveedores_pagado) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Productos bajo mínimo</span>
                                <span class="info-box-number">{{ number(resumen?.productos_stock_bajo) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Cuentas por cobrar</h3>
                                <div class="card-tools text-muted">Ventas pendientes: {{ number(resumen?.ventas_pendientes) }}</div>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Venta</th>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Pagado</th>
                                            <th>Saldo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="venta in cuentasPorCobrar" :key="venta.id">
                                            <td>
                                                <Link :href="route('venta.show', venta.id)">#{{ venta.id }}</Link>
                                            </td>
                                            <td>{{ venta.fecha_venta }}</td>
                                            <td>{{ venta.cliente }}</td>
                                            <td>{{ money(venta.total) }}</td>
                                            <td>{{ money(venta.monto_pagado) }}</td>
                                            <td><strong>{{ money(venta.saldo) }}</strong></td>
                                            <td><span :class="estadoClass(venta.estado)">{{ venta.estado }}</span></td>
                                        </tr>
                                        <tr v-if="!cuentasPorCobrar?.length">
                                            <td colspan="7" class="text-center text-muted py-3">No hay cuentas por cobrar.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card card-outline card-danger">
                            <div class="card-header">
                                <h3 class="card-title">Cuotas vencidas</h3>
                                <div class="card-tools text-muted">Cantidad: {{ number(resumen?.cuotas_vencidas) }}</div>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Vence</th>
                                            <th>Días</th>
                                            <th>Saldo</th>
                                            <th>Venta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="cuota in cuotasVencidas" :key="cuota.id">
                                            <td>{{ cuota.cliente }}</td>
                                            <td>{{ cuota.fecha_vencimiento }}</td>
                                            <td>{{ cuota.dias_mora }}</td>
                                            <td><strong>{{ money(cuota.saldo) }}</strong></td>
                                            <td>
                                                <Link :href="route('venta.show', cuota.id_venta)">#{{ cuota.id_venta }}</Link>
                                            </td>
                                        </tr>
                                        <tr v-if="!cuotasVencidas?.length">
                                            <td colspan="5" class="text-center text-muted py-3">No hay cuotas vencidas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Cuentas por pagar a proveedores</h3>
                                <div class="card-tools text-muted">Compras pendientes: {{ number(resumen?.compras_pendientes) }}</div>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Compra</th>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>Total</th>
                                            <th>Pagado</th>
                                            <th>Saldo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="compra in cuentasPorPagar" :key="compra.id">
                                            <td>
                                                <Link :href="route('compras.show', compra.id)">#{{ compra.id }}</Link>
                                            </td>
                                            <td>{{ compra.fecha_compra }}</td>
                                            <td>{{ compra.proveedor }}</td>
                                            <td>{{ money(compra.total) }}</td>
                                            <td>{{ money(compra.monto_pagado) }}</td>
                                            <td><strong>{{ money(compra.saldo) }}</strong></td>
                                            <td><span :class="estadoClass(compra.estado)">{{ compra.estado }}</span></td>
                                        </tr>
                                        <tr v-if="!cuentasPorPagar?.length">
                                            <td colspan="7" class="text-center text-muted py-3">No hay cuentas por pagar.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Stock bajo</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Stock</th>
                                            <th>Mín.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="producto in stockBajo" :key="producto.id">
                                            <td>{{ producto.codigo ?? '-' }}</td>
                                            <td>{{ producto.nombre }}</td>
                                            <td><strong>{{ producto.stock }}</strong></td>
                                            <td>{{ producto.stock_minimo }}</td>
                                        </tr>
                                        <tr v-if="!stockBajo?.length">
                                            <td colspan="4" class="text-center text-muted py-3">No hay productos bajo stock mínimo.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">Pagos de clientes recientes</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Venta</th>
                                            <th>Monto</th>
                                            <th>Método</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="pago in pagosRecientes" :key="pago.id">
                                            <td>{{ pago.fecha_pago }}</td>
                                            <td>{{ pago.cliente }}</td>
                                            <td>
                                                <Link v-if="pago.id_venta" :href="route('venta.show', pago.id_venta)">#{{ pago.id_venta }}</Link>
                                                <span v-else>-</span>
                                            </td>
                                            <td>{{ money(pago.monto) }}</td>
                                            <td>{{ pago.tipo_pago }}</td>
                                        </tr>
                                        <tr v-if="!pagosRecientes?.length">
                                            <td colspan="5" class="text-center text-muted py-3">No hay pagos de clientes en el periodo.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-outline card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Pagos a proveedores recientes</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>Compra</th>
                                            <th>Monto</th>
                                            <th>Método</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="pago in pagosProveedorRecientes" :key="pago.id">
                                            <td>{{ pago.fecha_pago }}</td>
                                            <td>{{ pago.proveedor }}</td>
                                            <td>
                                                <Link v-if="pago.id_compra" :href="route('compras.show', pago.id_compra)">#{{ pago.id_compra }}</Link>
                                                <span v-else>-</span>
                                            </td>
                                            <td>{{ money(pago.monto) }}</td>
                                            <td>{{ pago.metodo_pago }}</td>
                                        </tr>
                                        <tr v-if="!pagosProveedorRecientes?.length">
                                            <td colspan="5" class="text-center text-muted py-3">No hay pagos a proveedores en el periodo.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-dark">
                            <div class="card-header">
                                <h3 class="card-title">Kardex / movimientos recientes de inventario</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Producto</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="movimiento in movimientosInventario" :key="movimiento.id">
                                            <td>{{ movimiento.fecha }}</td>
                                            <td>{{ movimiento.producto }}</td>
                                            <td>
                                                <span :class="movimiento.tipo === 'entrada' ? 'badge badge-success' : 'badge badge-danger'">
                                                    {{ movimiento.tipo }}
                                                </span>
                                            </td>
                                            <td>{{ movimiento.cantidad }}</td>
                                            <td>{{ movimiento.descripcion }}</td>
                                        </tr>
                                        <tr v-if="!movimientosInventario?.length">
                                            <td colspan="5" class="text-center text-muted py-3">No hay movimientos de inventario.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <NoPermiso v-else mensaje="No tienes permisos para ver el control financiero." />
    </AppLayout>
</template>
