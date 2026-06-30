<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
    show: Boolean,
    venta: Object,
});

const emit = defineEmits(["close"]);

const form = useForm({
    id_cuota: "",
    monto: 0,
    tipo_pago: "efectivo",
    referencia: "",
    observaciones: "",
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const saldoCuota = (cuota) => {
    return Math.max(0, Number(cuota?.monto || 0) - Number(cuota?.monto_pagado || 0));
};

const cuotas = computed(() => {
    if (!props.venta?.planes?.length) return [];
    return props.venta.planes.flatMap((plan) => plan.cuotas || []);
});

const cuotasPendientes = computed(() => {
    return cuotas.value.filter((cuota) => cuota.estado !== "pagado" && saldoCuota(cuota) > 0);
});

const cuotaSeleccionada = computed(() => {
    if (!form.id_cuota) return null;
    return cuotas.value.find((cuota) => Number(cuota.id) === Number(form.id_cuota)) || null;
});

const saldoVenta = computed(() => {
    const venta = props.venta || {};
    return Math.max(0, Number(venta.saldo ?? venta.total ?? 0));
});

const montoMaximo = computed(() => {
    return cuotaSeleccionada.value ? saldoCuota(cuotaSeleccionada.value) : saldoVenta.value;
});

watch(() => props.venta, (venta) => {
    if (!venta) return;

    form.reset();
    const pendientes = (venta.planes || []).flatMap((plan) => plan.cuotas || []).filter((cuota) => cuota.estado !== "pagado" && saldoCuota(cuota) > 0);
    form.id_cuota = pendientes.length ? pendientes[0].id : "";
    const montoBase = pendientes.length ? saldoCuota(pendientes[0]) : Number(venta.saldo ?? venta.total ?? 0);
    form.monto = Number(montoBase.toFixed(2));
    form.tipo_pago = "efectivo";
    form.referencia = "";
    form.observaciones = "";
}, { immediate: true });

watch(() => form.id_cuota, () => {
    form.monto = Number(montoMaximo.value.toFixed(2));
});

const pagarConQr = () => {
    if (form.id_cuota) {
        router.visit(route("planes.pagarCuota2", form.id_cuota), { method: "get" });
        return;
    }

    router.visit(route("planes.pagarQR", props.venta.id), { method: "get" });
};

const submit = () => {
    if (cuotasPendientes.value.length && !form.id_cuota) {
        alert("Seleccione la cuota a pagar.");
        return;
    }

    if (form.tipo_pago === "PagoFacil" || form.tipo_pago === "qr") {
        pagarConQr();
        return;
    }

    if (Number(form.monto || 0) <= 0) {
        alert("El monto debe ser mayor a cero.");
        return;
    }

    if (Number(form.monto || 0) > montoMaximo.value + 0.01) {
        alert("El monto no puede ser mayor al saldo pendiente.");
        return;
    }

    form.post(route("pagos.store", props.venta.id), {
        preserveScroll: true,
        onSuccess: () => emit("close"),
    });
};
</script>

<template>
    <div v-if="show && venta" class="modal-mask">
        <div class="modal-container">
            <div class="modal-header bg-success text-white p-3 rounded-top d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title m-0">Registrar pago — Venta #{{ venta.id }}</h5>
                    <small>{{ venta.cliente?.nombre || 'Cliente no asignado' }}</small>
                </div>
                <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
            </div>

            <form @submit.prevent="submit">
                <div class="modal-body p-4 overflow-auto" style="max-height: 75vh;">
                    <div class="row text-center mb-3">
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">Total</small>
                                <strong>{{ formatCurrency(venta.total) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">Pagado</small>
                                <strong>{{ formatCurrency(venta.monto_pagado) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-2 bg-warning-subtle">
                                <small class="text-muted d-block">Saldo</small>
                                <strong>{{ formatCurrency(saldoVenta) }}</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold">Detalle de la venta</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ítem</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">P/U</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detalle in venta.detalles" :key="detalle.id">
                                    <td>{{ detalle.producto?.nombre || 'Producto eliminado' }}</td>
                                    <td class="text-end">{{ detalle.cantidad }}</td>
                                    <td class="text-end">{{ formatCurrency(detalle.precio) }}</td>
                                    <td class="text-end fw-bold">{{ formatCurrency(detalle.subtotal ?? detalle.cantidad * detalle.precio) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="cuotas.length" class="mb-4">
                        <h6 class="fw-bold">Plan de cuotas</h6>
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
                                            <span v-if="cuota.estado === 'pagado'" class="badge bg-success">Pagada</span>
                                            <span v-else class="badge bg-warning text-dark">Pendiente</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h6 class="fw-bold">Datos del pago</h6>
                    <div class="row g-3">
                        <div class="col-md-6" v-if="cuotasPendientes.length">
                            <label class="form-label">Aplicar a cuota</label>
                            <select v-model="form.id_cuota" class="form-control">
                                <option v-for="(cuota, index) in cuotasPendientes" :key="cuota.id" :value="cuota.id">
                                    Cuota {{ index + 1 }} — vence {{ cuota.fecha_vencimiento }} — saldo {{ formatCurrency(saldoCuota(cuota)) }}
                                </option>
                            </select>
                            <div v-if="form.errors.id_cuota" class="text-danger small">{{ form.errors.id_cuota }}</div>
                        </div>

                        <div :class="cuotasPendientes.length ? 'col-md-6' : 'col-md-12'">
                            <label class="form-label">Método de pago</label>
                            <select class="form-control" v-model="form.tipo_pago" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="qr">QR manual</option>
                                <option value="PagoFacil">PagoFácil / QR automático</option>
                            </select>
                            <div v-if="form.errors.tipo_pago" class="text-danger small">{{ form.errors.tipo_pago }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Monto</label>
                            <input type="number" class="form-control" v-model.number="form.monto" step="0.01" min="0.01" :max="montoMaximo" :readonly="form.tipo_pago === 'PagoFacil' || form.tipo_pago === 'qr'">
                            <small class="text-muted">Máximo: {{ formatCurrency(montoMaximo) }}</small>
                            <div v-if="form.errors.monto" class="text-danger small">{{ form.errors.monto }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Referencia</label>
                            <input type="text" class="form-control" v-model="form.referencia" placeholder="Recibo, transacción, comprobante">
                            <div v-if="form.errors.referencia" class="text-danger small">{{ form.errors.referencia }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control" v-model="form.observaciones" placeholder="Nota interna">
                        </div>
                    </div>

                    <div v-if="form.tipo_pago === 'PagoFacil' || form.tipo_pago === 'qr'" class="alert alert-info mt-3 mb-0">
                        Al confirmar, se abrirá la pantalla de generación de QR para el saldo seleccionado.
                    </div>
                </div>

                <div class="modal-footer bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-success" :disabled="form.processing || saldoVenta <= 0">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                        {{ form.tipo_pago === 'PagoFacil' || form.tipo_pago === 'qr' ? 'Generar QR' : 'Registrar pago' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.5);
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
    padding: 1rem;
}

.modal-container {
    background: white;
    width: min(950px, 98%);
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}
</style>
