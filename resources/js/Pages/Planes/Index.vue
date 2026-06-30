<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage, Head } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    planes: { type: Array, default: () => [] },
    num: Number,
});

const page = usePage();
const privilegios = computed(() => page.props.auth.privilegios || {});
const permiso = computed(() => privilegios.value.PlanPago || {});
const can = (funcionalidad) => Boolean(privilegios.value?.[funcionalidad]?.leer);
const canDelete = computed(() => Boolean(permiso.value.borrar));

const showDetalle = ref(null);
const showDelete = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const saldoCuota = (cuota) => Math.max(0, Number(cuota.monto || 0) - Number(cuota.monto_pagado || 0));

function deletePlanPago(id) {
    router.delete(route("planes.destroy", id), {
        preserveScroll: true,
        onSuccess: () => showDelete.value = null,
    });
}

let dataTable = null;
onMounted(() => {
    if (window.$) {
        dataTable = window.$("#planes").DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            order: [[0, "desc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
        });
    }
});

onUnmounted(() => {
    if (dataTable) {
        dataTable.destroy();
        dataTable = null;
    }
});
</script>

<template>
    <Head title="Planes de pago" />
    <AppLayout>
        <section class="content" v-if="can('PlanPago')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="card-title mb-0">
                        <i class="fas fa-calendar-alt mr-2"></i><b>PLANES DE PAGO</b>
                    </h1>
                    <small class="text-muted">Seguimiento de créditos, cuotas y saldos pendientes.</small>
                </div>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover align-middle" id="planes">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Venta</th>
                                <th>Cliente</th>
                                <th>Inicio</th>
                                <th>Frecuencia</th>
                                <th class="text-end">Cuotas</th>
                                <th class="text-end">Deuda</th>
                                <th class="text-end">Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="plan in planes" :key="plan.id">
                                <td>{{ plan.id }}</td>
                                <td>#{{ plan.id_venta }}</td>
                                <td>{{ plan.venta?.cliente?.nombre || 'No asignado' }}</td>
                                <td>{{ plan.fecha_inicio }}</td>
                                <td>{{ plan.frecuencia || 'mensual' }}</td>
                                <td class="text-end">{{ plan.cantidad_cuotas }}</td>
                                <td class="text-end fw-bold">{{ formatCurrency(plan.total_deuda) }}</td>
                                <td class="text-end fw-bold">{{ formatCurrency(plan.saldo_restante) }}</td>
                                <td>
                                    <span class="badge" :class="plan.estado === 'finalizado' ? 'bg-success' : 'bg-warning text-dark'">
                                        {{ plan.estado }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-primary btn-sm mr-1" @click="showDetalle = plan">Ver cuotas</button>
                                    <button v-if="canDelete" class="btn btn-danger btn-sm" @click="showDelete = plan">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showDetalle" class="modal-mask">
                <div class="modal-container modal-lg-custom">
                    <div class="modal-header d-flex justify-content-between align-items-center bg-light">
                        <div>
                            <h5 class="modal-title mb-0">Cuotas del plan #{{ showDetalle.id }}</h5>
                            <small>Venta #{{ showDetalle.id_venta }} — {{ showDetalle.venta?.cliente?.nombre || 'Cliente no asignado' }}</small>
                        </div>
                        <button type="button" class="btn-close" @click="showDetalle = null"></button>
                    </div>
                    <div class="modal-body">
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
                                    <tr v-for="(cuota, index) in showDetalle.cuotas || []" :key="cuota.id">
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
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="showDetalle = null">Cerrar</button>
                    </div>
                </div>
            </div>

            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Eliminar plan de pago?</h5>
                    <p>El plan #<strong>{{ showDelete.id }}</strong> será marcado como inactivo.</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deletePlanPago(showDelete.id)">Eliminar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver los planes de pago." />
    </AppLayout>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.35);
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    align-items: center;
    justify-content: center;
    display: flex;
    width: 100vw;
    padding: 1rem;
}

.modal-container {
    background: white;
    width: min(500px, 90%);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    padding: 20px;
}

.modal-lg-custom {
    width: min(850px, 96%);
    padding: 0;
}

.modal-lg-custom .modal-body,
.modal-lg-custom .modal-header,
.modal-lg-custom .modal-footer {
    padding: 1rem;
}
</style>
