<script setup>
import { computed, ref } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import NoPermiso from "@/Components/NoPermiso.vue";
import { useDateFormatter } from "../../Composables/useDateFormatter";

const { formatDate, formatDateTime } = useDateFormatter();
const props = defineProps({
    solicitudes: { type: Array, default: () => [] },
    departamentos: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    proveedores: { type: Array, default: () => [] },
});
const page = usePage();
const errors = computed(() => page.props.errors || {});
const can = (funcionalidad) =>
    page.props.auth?.privilegios?.[funcionalidad]?.leer;
const canAdd = computed(() => page.props.auth?.privilegios?.Solicitud?.agregar);
const canApprove = computed(
    () => page.props.auth?.privilegios?.Solicitud?.modificar,
);
const canDelete = computed(
    () => page.props.auth?.privilegios?.Solicitud?.borrar,
);
const showAdd = ref(false);
const showEstado = ref(null);
const showDelete = ref(null);
const showApprove = ref(null);
const showReject = ref(null);
const today = new Date().toISOString().slice(0, 10);
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;

const form = ref({
    id_proveedor: "",
    id_departamento: "",
    descripcion: "",
    justificacion: "",
    fecha_requerida: today,
    metodo_pago: "efectivo",
    moneda: "BOB",
    observaciones: "",
    detalles: [
        {
            id_producto: "",
            nombre_articulo: "",
            cantidad: 1,
            precio_estimado: 0,
        },
    ],
});
const approveForm = ref({ observacion_aprobacion: "" });
const rejectForm = ref({ motivo_rechazo: "" });
const estadoForm = ref({ id: null, estado: "pendiente", observaciones: "" });
const totalEstimado = computed(() =>
    form.value.detalles.reduce(
        (a, d) => a + Number(d.cantidad || 0) * Number(d.precio_estimado || 0),
        0,
    ),
);

function resetForm() {
    form.value = {
        id_departamento: "",
        descripcion: "",
        justificacion: "",
        fecha_requerida: today,
        moneda: "BOB",
        observaciones: "",
        detalles: [
            {
                id_producto: "",
                nombre_articulo: "",
                cantidad: 1,
                precio_estimado: 0,
            },
        ],
    };
}
function addDetalle() {
    form.value.detalles.push({
        id_producto: "",
        nombre_articulo: "",
        cantidad: 1,
        precio_estimado: 0,
    });
}
function removeDetalle(index) {
    if (form.value.detalles.length > 1) form.value.detalles.splice(index, 1);
}
function setProducto(detalle) {
    const p = props.productos.find(
        (x) => Number(x.id) === Number(detalle.id_producto),
    );
    if (p) {
        detalle.nombre_articulo = p.nombre;
        detalle.precio_estimado = Number(p.precio_compra || p.precio || 0);
    }
}
function storeSolicitud() {
    router.post(route("solicitudes.store"), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAdd.value = false;
            resetForm();
        },
    });
}
function openEstado(solicitud) {
    showEstado.value = solicitud.id;
    estadoForm.value = {
        id: solicitud.id,
        estado: solicitud.estado,
        observaciones: solicitud.observaciones || "",
    };
}
function updateEstado() {
    router.put(
        route("solicitudes.update", estadoForm.value.id),
        estadoForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                showEstado.value = null;
            },
        },
    );
}
function aprobarSolicitud() {
    if (!showApprove.value) return;
    router.put(
        route("solicitudes.aprobar", showApprove.value.id),
        approveForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                showApprove.value = null;
                approveForm.value = { observacion_aprobacion: "" };
            },
        },
    );
}
function rechazarSolicitud() {
    if (!showReject.value) return;
    router.put(
        route("solicitudes.rechazar", showReject.value.id),
        rejectForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                showReject.value = null;
                rejectForm.value = { motivo_rechazo: "" };
            },
        },
    );
}
function deleteSolicitud(id) {
    router.delete(route("solicitudes.destroy", id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = null;
        },
    });
}
function badgeClass(estado) {
    return estado === "aprobada"
        ? "badge badge-success"
        : estado === "rechazada" || estado === "anulada"
          ? "badge badge-danger"
          : estado === "atendida"
            ? "badge badge-info"
            : "badge badge-warning";
}
function puedeAnular(solicitud) {
    if (!canDelete.value) return false;
    if (solicitud.estado === "pendiente" || solicitud.estado === "rechazada")
        return true;
    return (
        solicitud.estado === "aprobada" &&
        Number(solicitud.monto_pagado || 0) === 0
    );
}
</script>

<template>
    <AppLayout title="Solicitudes">
        <section class="content" v-if="can('Solicitud')">
            <div
                class="card-header d-flex justify-content-between align-items-center"
            >
                <h1 class="card-title mb-0">
                    <i class="fas fa-clipboard-list mr-2"></i
                    ><b>SOLICITUDES DE COMPRAS</b>
                </h1>
                <button
                    v-if="canAdd"
                    class="btn btn-success"
                    @click="showAdd = true"
                >
                    <i class="fa fa-plus"></i>&nbsp; Nueva solicitud
                </button>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>FECHA</th>
                                <th>DEPARTAMENTO</th>
                                <th>DESCRIPCIÓN</th>
                                <th>REQUERIDA</th>
                                <th>ESTADO</th>
                                <th>ÍTEMS</th>
                                <th>TOTAL</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="solicitud in solicitudes"
                                :key="solicitud.id"
                            >
                                <td>{{ solicitud.id }}</td>
                                <td>
                                    {{ formatDate(solicitud.fecha_solicitud) }}
                                </td>
                                <td>
                                    {{ solicitud.departamento?.nombre || "-" }}
                                </td>
                                <td>
                                    <b>{{ solicitud.descripcion }}</b
                                    ><br /><small>{{
                                        solicitud.justificacion || ""
                                    }}</small>
                                </td>
                                <td>
                                    {{
                                        formatDate(solicitud.fecha_requerida) ||
                                        "-"
                                    }}
                                </td>
                                <td>
                                    <span
                                        :class="badgeClass(solicitud.estado)"
                                        >{{ solicitud.estado }}</span
                                    >
                                </td>
                                <td>{{ solicitud.detalles?.length || 0 }}</td>
                                <td>
                                    {{
                                        money(
                                            (solicitud.detalles || []).reduce(
                                                (a, d) =>
                                                    a + Number(d.importe || 0),
                                                0,
                                            ),
                                        )
                                    }}
                                </td>
                                <td class="text-nowrap">
                                    <Link
                                        :href="
                                            route(
                                                'solicitudes.show',
                                                solicitud.id,
                                            )
                                        "
                                        class="btn btn-xs btn-outline-primary mr-1"
                                        title="Ver"
                                        ><i class="fa fa-eye"></i
                                    ></Link>
                                    <button
                                        v-if="
                                            canApprove &&
                                            solicitud.estado === 'pendiente'
                                        "
                                        class="btn btn-xs btn-success mr-1"
                                        @click="showApprove = solicitud"
                                        title="Aprobar"
                                    >
                                        <i class="fa fa-check"></i>
                                    </button>
                                    <button
                                        v-if="
                                            canApprove &&
                                            solicitud.estado === 'pendiente'
                                        "
                                        class="btn btn-xs btn-danger mr-1"
                                        @click="showReject = solicitud"
                                        title="Rechazar"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
                                    <button
                                        v-if="puedeAnular(solicitud)"
                                        class="btn btn-xs btn-outline-danger"
                                        @click="showDelete = solicitud"
                                        title="Anular"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="solicitudes.length === 0">
                                <td colspan="9" class="text-center text-muted">
                                    No hay solicitudes registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showAdd" class="modal-mask">
                <div class="modal-container modal-xl-custom">
                    <div class="modal-header">
                        <h5>Nueva solicitud</h5>
                        <button
                            class="btn-close"
                            @click="showAdd = false"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label>Departamento</label
                                ><select
                                    class="form-control"
                                    v-model="form.id_departamento"
                                >
                                    <option value="">Sin departamento</option>
                                    <option
                                        v-for="d in departamentos"
                                        :key="d.id"
                                        :value="d.id"
                                    >
                                        {{ d.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Fecha requerida</label
                                ><input
                                    type="date"
                                    class="form-control"
                                    v-model="form.fecha_requerida"
                                />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Proveedor *</label
                                ><select
                                    class="form-control"
                                    v-model="form.id_proveedor"
                                >
                                    <option value="">Seleccione</option>
                                    <option
                                        v-for="p in proveedores"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.nombre }}
                                    </option>
                                </select>
                                <p
                                    v-if="errors.id_proveedor"
                                    class="text-danger"
                                >
                                    {{ errors.id_proveedor }}
                                </p>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Moneda</label
                                ><input
                                    class="form-control"
                                    v-model="form.moneda"
                                />
                            </div>
                            <div class="col-md-12 mb-2">
                                <label>Descripción *</label
                                ><input
                                    class="form-control"
                                    v-model="form.descripcion"
                                />
                                <p
                                    v-if="errors.descripcion"
                                    class="text-danger"
                                >
                                    {{ errors.descripcion }}
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label>Método propuesto</label
                                ><select
                                    class="form-control"
                                    v-model="form.metodo_pago"
                                >
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">
                                        Transferencia
                                    </option>
                                    <option value="qr">QR</option>
                                    <option value="pagofacil">PagoFácil</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="otro">Otro</option>
                                </select>
                                <p
                                    v-if="errors.metodo_pago"
                                    class="text-danger"
                                >
                                    {{ errors.metodo_pago }}
                                </p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label>Observaciones</label
                                ><textarea
                                    class="form-control"
                                    rows="2"
                                    v-model="form.observaciones"
                                ></textarea>
                            </div>
                        </div>
                        <h6 class="mt-2"><b>Detalle solicitado</b></h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto existente</th>
                                    <th>Artículo</th>
                                    <th width="110">Cantidad</th>
                                    <th width="150">Precio est.</th>
                                    <th width="150">Importe</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(detalle, index) in form.detalles"
                                    :key="index"
                                >
                                    <td>
                                        <select
                                            class="form-control"
                                            v-model="detalle.id_producto"
                                            @change="setProducto(detalle)"
                                        >
                                            <option value="">
                                                No registrado
                                            </option>
                                            <option
                                                v-for="p in productos"
                                                :key="p.id"
                                                :value="p.id"
                                            >
                                                {{ p.nombre }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            class="form-control"
                                            v-model="detalle.nombre_articulo"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="1"
                                            class="form-control"
                                            v-model.number="detalle.cantidad"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            v-model.number="
                                                detalle.precio_estimado
                                            "
                                        />
                                    </td>
                                    <td>
                                        {{
                                            money(
                                                Number(detalle.cantidad || 0) *
                                                    Number(
                                                        detalle.precio_estimado ||
                                                            0,
                                                    ),
                                            )
                                        }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-danger btn-sm"
                                            @click="removeDetalle(index)"
                                            :disabled="
                                                form.detalles.length === 1
                                            "
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between">
                            <button
                                class="btn btn-outline-primary btn-sm"
                                @click="addDetalle"
                            >
                                <i class="fa fa-plus"></i> Agregar ítem</button
                            ><b>Total estimado: {{ money(totalEstimado) }}</b>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showAdd = false"
                        >
                            Cancelar</button
                        ><button
                            class="btn btn-primary"
                            @click="storeSolicitud"
                        >
                            Guardar solicitud
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showEstado" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Actualizar estado</h5>
                        <button
                            class="btn-close"
                            @click="showEstado = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <label>Estado</label
                        ><select
                            class="form-control mb-2"
                            v-model="estadoForm.estado"
                        >
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                            <option value="atendida">Atendida</option>
                            <option value="anulada">Anulada</option>
                        </select>
                        <label>Observaciones</label
                        ><textarea
                            class="form-control"
                            rows="3"
                            v-model="estadoForm.observaciones"
                        ></textarea>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showEstado = null"
                        >
                            Cancelar</button
                        ><button class="btn btn-primary" @click="updateEstado">
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Anular solicitud</h5>
                        <button
                            class="btn-close"
                            @click="showDelete = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        ¿Anular la solicitud Nº <b>{{ showDelete.id }}</b
                        >?
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showDelete = null"
                        >
                            Cancelar</button
                        ><button
                            class="btn btn-danger"
                            @click="deleteSolicitud(showDelete.id)"
                        >
                            Anular
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showApprove" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Aprobar solicitud de compra</h5>
                        <button
                            class="btn-close"
                            @click="showApprove = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            ¿Aprobar la solicitud Nº
                            <b>{{ showApprove.id }}</b> por
                            {{ money(showApprove.total) }}?
                        </p>
                        <div class="alert alert-warning py-2">
                            Al aprobar, el sistema generará una compra.
                        </div>
                        <label>Observación de aprobación</label>
                        <textarea
                            class="form-control"
                            rows="3"
                            v-model="approveForm.observacion_aprobacion"
                            placeholder="Ej.: Cotización verificada y autorizada por administración."
                        ></textarea>
                        <p
                            v-if="errors.observacion_aprobacion"
                            class="text-danger"
                        >
                            {{ errors.observacion_aprobacion }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showApprove = null"
                        >
                            Cancelar
                        </button>
                        <button
                            class="btn btn-success"
                            @click="aprobarSolicitud"
                        >
                            Aprobar y generar compra
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showReject" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Rechazar solicitud de compra</h5>
                        <button
                            class="btn-close"
                            @click="showReject = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Indica el motivo para rechazar la solicitud Nº
                            <b>{{ showReject.id }}</b
                            >.
                        </p>
                        <label>Motivo de rechazo *</label>
                        <textarea
                            class="form-control"
                            rows="3"
                            v-model="rejectForm.motivo_rechazo"
                            placeholder="Ej.: Precio elevado, solicitar nueva cotización."
                        ></textarea>
                        <p v-if="errors.motivo_rechazo" class="text-danger">
                            {{ errors.motivo_rechazo }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showReject = null"
                        >
                            Cancelar</button
                        ><button
                            class="btn btn-danger"
                            @click="rechazarSolicitud"
                        >
                            Rechazar solicitud
                        </button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver solicitudes." />
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
    align-items: flex-start;
    justify-content: center;
    display: flex;
    width: 100vw;
    overflow-y: auto;
    padding: 20px;
}
.modal-container {
    background: #fff;
    width: min(560px, 94%);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
}
.modal-xl-custom {
    width: min(1100px, 98%);
}
</style>
