<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    show: Boolean,
    venta: Object,
});

const emit = defineEmits(["close"]);

const hoy = new Date().toISOString().split("T")[0];
const previewCuotas = ref([]);

const saldoVenta = computed(() => Math.max(0, Number(props.venta?.saldo ?? props.venta?.total ?? 0)));

const form = useForm({
    cantidad_cuotas: 1,
    total_deuda: 0,
    fecha_inicio: hoy,
    frecuencia: "mensual",
    estado: "en_curso",
    cuotas: [],
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

watch(() => props.venta, (venta) => {
    if (!venta) return;
    form.reset();
    form.cantidad_cuotas = 1;
    form.total_deuda = Number(saldoVenta.value.toFixed(2));
    form.fecha_inicio = hoy;
    form.frecuencia = "mensual";
    form.estado = "en_curso";
    previewCuotas.value = [];
}, { immediate: true });

const sumarPeriodo = (fechaBase, frecuencia, indice) => {
    const fecha = new Date(`${fechaBase}T00:00:00`);

    if (frecuencia === "semanal") {
        fecha.setDate(fecha.getDate() + (7 * indice));
    } else if (frecuencia === "quincenal") {
        fecha.setDate(fecha.getDate() + (15 * indice));
    } else {
        fecha.setMonth(fecha.getMonth() + indice);
    }

    return fecha.toISOString().split("T")[0];
};

const generarCuotas = () => {
    const n = Number(form.cantidad_cuotas || 0);
    const total = Number(form.total_deuda || 0);

    if (n < 1 || total <= 0) {
        previewCuotas.value = [];
        return;
    }

    const base = Number((Math.floor((total / n) * 100) / 100).toFixed(2));
    let acumulado = 0;
    previewCuotas.value = [];

    for (let i = 0; i < n; i++) {
        const monto = i === n - 1 ? Number((total - acumulado).toFixed(2)) : base;
        acumulado = Number((acumulado + monto).toFixed(2));
        previewCuotas.value.push({
            numero: i + 1,
            monto,
            fecha: sumarPeriodo(form.fecha_inicio, form.frecuencia, i),
        });
    }
};

watch(() => [form.cantidad_cuotas, form.fecha_inicio, form.frecuencia, form.total_deuda], generarCuotas, { deep: true });

const submit = () => {
    if (previewCuotas.value.length === 0) generarCuotas();

    form.cuotas = previewCuotas.value.map((cuota) => ({
        monto: cuota.monto,
        fecha: cuota.fecha,
    }));

    form.post(route("planes.store", props.venta.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            previewCuotas.value = [];
        },
    });
};
</script>

<template>
    <div v-if="show && venta" class="modal-mask">
        <div class="modal-container">
            <div class="modal-header bg-warning text-dark p-3 rounded-top d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title m-0 fw-bold">Crear plan de pago — Venta #{{ venta.id }}</h5>
                    <small>Se generará un calendario de cuotas sobre el saldo pendiente.</small>
                </div>
                <button type="button" class="btn-close" @click="$emit('close')"></button>
            </div>

            <form @submit.prevent="submit">
                <div class="modal-body p-4">
                    <div class="row text-center mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">Total venta</small>
                                <strong>{{ formatCurrency(venta.total) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">Pagado</small>
                                <strong>{{ formatCurrency(venta.monto_pagado) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-warning-subtle">
                                <small class="text-muted d-block">Saldo a financiar</small>
                                <strong>{{ formatCurrency(saldoVenta) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cantidad de cuotas</label>
                            <input type="number" class="form-control" v-model.number="form.cantidad_cuotas" min="1" required>
                            <div v-if="form.errors.cantidad_cuotas" class="text-danger small">{{ form.errors.cantidad_cuotas }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Frecuencia</label>
                            <select class="form-control" v-model="form.frecuencia">
                                <option value="semanal">Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Primera cuota</label>
                            <input type="date" class="form-control" v-model="form.fecha_inicio" required>
                            <div v-if="form.errors.fecha_inicio" class="text-danger small">{{ form.errors.fecha_inicio }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Total deuda</label>
                            <input type="number" step="0.01" class="form-control bg-light" v-model.number="form.total_deuda" readonly>
                            <div v-if="form.errors.total_deuda" class="text-danger small">{{ form.errors.total_deuda }}</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold text-muted">Previsualización de cuotas</label>
                        <div class="table-responsive border rounded" style="max-height: 260px; overflow-y: auto;">
                            <table class="table table-striped table-sm mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th># Cuota</th>
                                        <th class="text-end">Monto</th>
                                        <th>Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cuota in previewCuotas" :key="cuota.numero">
                                        <td>{{ cuota.numero }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(cuota.monto) }}</td>
                                        <td>{{ cuota.fecha }}</td>
                                    </tr>
                                    <tr v-if="previewCuotas.length === 0">
                                        <td colspan="3" class="text-center text-muted py-3">No se generaron cuotas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold" :disabled="form.processing || saldoVenta <= 0">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                        Crear plan
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
    width: min(850px, 96%);
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}
</style>
