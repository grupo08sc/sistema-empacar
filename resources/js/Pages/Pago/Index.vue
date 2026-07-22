<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage, Head } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    pagos: { type: Array, default: () => [] },
    num: Number,
});

const page = usePage();
const privilegios = computed(() => page.props.auth.privilegios || {});
const permiso = computed(() => privilegios.value.Pago || {});
const can = (funcionalidad) => Boolean(privilegios.value?.[funcionalidad]?.leer);
const canEdit = computed(() => Boolean(permiso.value.modificar));
const canDelete = computed(() => Boolean(permiso.value.borrar));

const showEdit = ref(null);
const showDelete = ref(null);
const editPago = ref({ id: null, estado_pago: "", tipo_pago: "", referencia: "" });

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-BO", {
        style: "currency",
        currency: "BOB",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

function updatePago() {
    router.put(route("pago.update", editPago.value.id), editPago.value, {
        preserveScroll: true,
        onSuccess: () => showEdit.value = null,
    });
}

function deletePago(id) {
    router.delete(route("pago.destroy", id), {
        preserveScroll: true,
        onSuccess: () => showDelete.value = null,
    });
}

let dataTable = null;
onMounted(() => {
    if (window.$) {
        dataTable = window.$("#pagos").DataTable({
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
    <Head title="Pagos" />
    <AppLayout>
        <section class="content" v-if="can('Pago')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="card-title mb-0">
                        <i class="fas fa-money-bill-wave mr-2"></i><b>Luis: GESTIÓN DE PAGOS</b>
                    </h1>
                    <small class="text-muted">Historial de pagos de clientes y cuotas.</small>
                </div>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover align-middle" id="pagos">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Venta</th>
                                <th>Cliente</th>
                                <th>Cuota</th>
                                <th class="text-end">Monto</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pago in pagos" :key="pago.id">
                                <td>{{ pago.id }}</td>
                                <td>#{{ pago.id_venta || pago.venta?.id || '-' }}</td>
                                <td>{{ pago.cliente?.nombre || pago.venta?.cliente?.nombre || 'N/A' }}</td>
                                <td>{{ pago.id_cuota ? `#${pago.id_cuota}` : 'Venta' }}</td>
                                <td class="text-end fw-bold">{{ formatCurrency(pago.monto) }}</td>
                                <td>{{ pago.tipo_pago }}</td>
                                <td>{{ pago.referencia || pago.transaction_id || '-' }}</td>
                                <td>{{ pago.fecha_pago }}</td>
                                <td><span class="badge bg-success">{{ pago.estado_pago }}</span></td>
                                <td class="text-nowrap">
                                    <button v-if="canEdit" class="btn btn-primary btn-sm mr-1" @click.prevent="showEdit = pago; editPago = { ...pago }">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button v-if="canDelete" class="btn btn-danger btn-sm" @click.prevent="showDelete = pago">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showEdit" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar pago #{{ showEdit.id }}</h5>
                        <button type="button" class="btn-close" @click="showEdit = null"></button>
                    </div>
                    <form @submit.prevent="updatePago">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Método de pago</label>
                                <input v-model="editPago.tipo_pago" type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select v-model="editPago.estado_pago" class="form-control" required>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="parcial">Parcial</option>
                                    <option value="pagado">Pagado</option>
                                    <option value="excedente">Excedente</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Referencia</label>
                                <input v-model="editPago.referencia" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer text-end">
                            <button class="btn btn-secondary" type="button" @click="showEdit = null">Cancelar</button>
                            <button class="btn btn-primary" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Eliminar pago?</h5>
                    <p>El pago #<strong>{{ showDelete.id }}</strong> será marcado como inactivo.</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deletePago(showDelete.id)">Eliminar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver los pagos." />
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
    width: min(520px, 90%);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
}
</style>
