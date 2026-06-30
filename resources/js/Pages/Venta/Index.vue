<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import RegistrarPagoModal from "./RegistrarPago.vue";
import PlanPagoModal from "./PlanPago.vue";
import AgregarVentaModal from "./Create.vue";
import ShowVentaModal from "./Show.vue";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    ventas: { type: Array, default: () => [] },
    clientes: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    num: Number,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const privilegios = computed(() => page.props.auth.privilegios || {});
const permisoVenta = computed(() => privilegios.value.Venta || {});

const can = (funcionalidad) => Boolean(privilegios.value?.[funcionalidad]?.leer);
const canAdd = computed(() => Boolean(permisoVenta.value.agregar));
const canDelete = computed(() => Boolean(permisoVenta.value.borrar));
const isCliente = computed(() => user.value?.rol?.nombre === "Cliente");

const showCreate = ref(false);
const verDetalles = ref(null);
const registrarPago = ref(null);
const planPago = ref(null);
const ventaToDelete = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const saldoVenta = (venta) => Math.max(0, Number(venta.saldo ?? venta.total ?? 0));
const tienePlan = (venta) => Boolean(venta.planes?.length || venta.plan);
const puedeCrearPlan = (venta) => saldoVenta(venta) > 0 && !tienePlan(venta);

const estadoClase = (estado) => {
    if (estado === "pagado") return "bg-success";
    if (estado === "parcial") return "bg-info text-dark";
    if (estado === "anulado") return "bg-danger";
    return "bg-warning text-dark";
};

function deleteVenta() {
    if (!ventaToDelete.value) return;
    router.delete(route("venta.destroy", ventaToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => ventaToDelete.value = null,
    });
}

let dataTable = null;
onMounted(() => {
    if (window.$) {
        dataTable = window.$("#ventas").DataTable({
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
    <Head title="Ventas" />
    <AppLayout>
        <section class="content" v-if="can('Venta')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="card-title mb-0"><i class="fas fa-shopping-cart mr-2"></i><b>GESTIÓN DE VENTAS</b></h1>
                    <small class="text-muted">Ventas al contado, crédito, pagos parciales y cuotas.</small>
                </div>
                <div class="d-flex align-items-center ml-auto">
                    <button v-if="canAdd && !isCliente" class="btn btn-success" @click="showCreate = true">
                        <i class="fa fa-plus"></i>&nbsp; Nueva venta
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3 mb-2">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4>{{ ventas.length }}</h4>
                            <p>Ventas registradas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4>{{ ventas.filter(v => v.estado === 'pagado').length }}</h4>
                            <p>Pagadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h4>{{ ventas.filter(v => saldoVenta(v) > 0).length }}</h4>
                            <p>Con saldo</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h4>{{ ventas.filter(v => tienePlan(v)).length }}</h4>
                            <p>Con plan de cuotas</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover align-middle" id="ventas">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Registrado por</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="venta in ventas" :key="venta.id">
                                <td>{{ venta.id }}</td>
                                <td>{{ venta.cliente?.nombre || 'No asignado' }} {{ venta.cliente?.apellido || '' }}</td>
                                <td>{{ venta.usuario?.nombre || 'No asignado' }}</td>
                                <td>{{ venta.fecha_venta }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ venta.tipo_pago || 'contado' }}</span>
                                    <span v-if="tienePlan(venta)" class="badge bg-primary ms-1">cuotas</span>
                                </td>
                                <td class="text-end fw-bold">{{ formatCurrency(venta.total) }}</td>
                                <td class="text-end">{{ formatCurrency(venta.monto_pagado) }}</td>
                                <td class="text-end fw-bold">{{ formatCurrency(saldoVenta(venta)) }}</td>
                                <td><span class="badge" :class="estadoClase(venta.estado)">{{ venta.estado }}</span></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-primary btn-sm mr-1" @click.prevent="verDetalles = venta">
                                        Ver
                                    </button>
                                    <template v-if="!isCliente">
                                        <button class="btn btn-success btn-sm mr-1" :disabled="saldoVenta(venta) <= 0" @click.prevent="registrarPago = venta">
                                            Pagar
                                        </button>
                                        <button class="btn btn-warning btn-sm mr-1" :disabled="!puedeCrearPlan(venta)" @click.prevent="planPago = venta">
                                            Plan
                                        </button>
                                        <button v-if="canDelete" class="btn btn-danger btn-sm" @click.prevent="ventaToDelete = venta">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AgregarVentaModal :show="showCreate" :clientes="clientes" :productos="productos" @close="showCreate = false" />
            <ShowVentaModal v-if="verDetalles" :show="!!verDetalles" :venta="verDetalles" @close="verDetalles = null" />
            <RegistrarPagoModal v-if="registrarPago" :show="!!registrarPago" :venta="registrarPago" @close="registrarPago = null" />
            <PlanPagoModal v-if="planPago" :show="!!planPago" :venta="planPago" @close="planPago = null" />

            <div v-if="ventaToDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Eliminar venta?</h5>
                    <p>La venta #<strong>{{ ventaToDelete.id }}</strong> será marcada como inactiva.</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="ventaToDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deleteVenta">Eliminar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permisos para ver las ventas." />
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
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-container {
    background: white;
    width: min(420px, 90%);
    border-radius: 8px;
    padding: 20px;
}
</style>
