<script setup>
import { computed } from "vue";

const props = defineProps({
    show: Boolean,
    venta: Object,
});

const emit = defineEmits(["close"]);

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const cuotas = computed(() => {
    if (!props.venta?.planes?.length) return [];
    return props.venta.planes.flatMap((plan) => plan.cuotas || []);
});

const saldoCuota = (cuota) => Math.max(0, Number(cuota.monto || 0) - Number(cuota.monto_pagado || 0));

const estadoClase = (estado) => {
    if (estado === "pagado") return "bg-success";
    if (estado === "parcial") return "bg-info text-dark";
    if (estado === "anulado") return "bg-danger";
    return "bg-warning text-dark";
};
</script>

<template>
    <div v-if="show && venta" class="modal-mask">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title mb-0">
                            Detalle de venta #{{ venta.id }}
                            <span class="badge ms-2" :class="estadoClase(venta.estado)">{{ venta.estado }}</span>
                        </h5>
                        <small>{{ venta.tipo_pago || 'sin condición de pago' }}</small>
                    </div>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Cliente:</strong> {{ venta.cliente?.nombre || 'No asignado' }} {{ venta.cliente?.apellido || '' }}</p>
                            <p class="mb-1"><strong>Vendedor:</strong> {{ venta.usuario?.nombre || 'No asignado' }}</p>
                            <p class="mb-1"><strong>Fecha:</strong> {{ venta.fecha_venta }}</p>
                        </div>
                        <div class="col-md-6">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-light">
                                        <small class="text-muted d-block">Total</small>
                                        <strong>{{ formatCurrency(venta.total) }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-light">
                                        <small class="text-muted d-block">Pagado</small>
                                        <strong>{{ formatCurrency(venta.monto_pagado) }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-warning-subtle">
                                        <small class="text-muted d-block">Saldo</small>
                                        <strong>{{ formatCurrency(venta.saldo) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">Productos</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Precio unitario</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detalle in venta.detalles" :key="detalle.id">
                                    <td>{{ detalle.producto?.nombre || 'Producto eliminado' }}</td>
                                    <td class="text-end">{{ detalle.cantidad }}</td>
                                    <td class="text-end">{{ formatCurrency(detalle.precio) }}</td>
                                    <td class="text-end fw-bold">{{ formatCurrency(detalle.subtotal ?? detalle.precio * detalle.cantidad) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Subtotal</th>
                                    <th class="text-end">{{ formatCurrency(venta.subtotal) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Descuento</th>
                                    <th class="text-end">{{ formatCurrency(venta.descuento) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">{{ formatCurrency(venta.total) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div v-if="cuotas.length" class="mt-4">
                        <h6 class="fw-bold mb-3">Plan de cuotas</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimiento</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end">Pagado</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(cuota, index) in cuotas" :key="cuota.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ cuota.fecha_vencimiento }}</td>
                                        <td class="text-end">{{ formatCurrency(cuota.monto) }}</td>
                                        <td class="text-end">{{ formatCurrency(cuota.monto_pagado) }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(saldoCuota(cuota)) }}</td>
                                        <td>
                                            <span class="badge" :class="cuota.estado === 'pagado' ? 'bg-success' : 'bg-warning text-dark'">
                                                {{ cuota.estado }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Historial de pagos</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Cuota</th>
                                        <th class="text-end">Monto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="pago in venta.pagos || []" :key="pago.id">
                                        <td>{{ pago.fecha_pago }}</td>
                                        <td>{{ pago.tipo_pago }}</td>
                                        <td>{{ pago.referencia || '-' }}</td>
                                        <td>{{ pago.id_cuota ? `#${pago.id_cuota}` : 'Venta' }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(pago.monto) }}</td>
                                        <td><span class="badge bg-success">{{ pago.estado_pago }}</span></td>
                                    </tr>
                                    <tr v-if="!venta.pagos || venta.pagos.length === 0">
                                        <td colspan="6" class="text-center text-muted">No hay pagos registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="venta.observaciones" class="alert alert-secondary mt-3 mb-0">
                        <strong>Observaciones:</strong> {{ venta.observaciones }}
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 1055;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 30px 15px;
    overflow-y: auto;
}

.modal-dialog {
    width: 100%;
    margin: 0 auto;
}

.modal-content {
    animation: slideDown 0.25s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
