<script setup>
import { computed, ref } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import NoPermiso from "@/Components/NoPermiso.vue";
import { useDateFormatter } from "../../Composables/useDateFormatter";

const { formatDate, formatDateTime } = useDateFormatter();

const props = defineProps({
    compras: { type: Array, default: () => [] },
    proveedores: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const can = (funcionalidad) =>
    page.props.auth?.privilegios?.[funcionalidad]?.leer;
const canAdd = computed(() => page.props.auth?.privilegios?.Compra?.agregar);
const canApprove = computed(
    () => page.props.auth?.privilegios?.Compra?.modificar,
);
const canDelete = computed(() => page.props.auth?.privilegios?.Compra?.borrar);
const showAdd = ref(false);
const showDelete = ref(null);
const showApprove = ref(null);
const showReject = ref(null);
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;
const today = new Date().toISOString().slice(0, 10);

const form = ref({
    id_proveedor: "",
    fecha_compra: today,
    descuento: 0,
    monto_pagado: 0,
    metodo_pago: "efectivo",
    referencia: "",
    observaciones: "",
    detalles: [{ id_producto: "", cantidad: 1, precio_unitario: 0 }],
});

const approveForm = ref({ observacion_aprobacion: "" });
const rejectForm = ref({ motivo_rechazo: "" });

const subtotal = computed(() =>
    form.value.detalles.reduce(
        (acc, d) =>
            acc + Number(d.cantidad || 0) * Number(d.precio_unitario || 0),
        0,
    ),
);
const total = computed(() =>
    Math.max(0, subtotal.value - Number(form.value.descuento || 0)),
);
const saldo = computed(() =>
    Math.max(0, total.value - Number(form.value.monto_pagado || 0)),
);
const pendientes = computed(() =>
    props.compras.filter((c) => c.estado_aprobacion === "pendiente"),
);
const aprobadas = computed(() =>
    props.compras.filter((c) => c.estado_aprobacion === "aprobada"),
);
const rechazadas = computed(() =>
    props.compras.filter((c) => c.estado_aprobacion === "rechazada"),
);
const totalAprobado = computed(() =>
    aprobadas.value.reduce((a, c) => a + Number(c.total || 0), 0),
);

function resetForm() {
    form.value = {
        id_proveedor: "",
        fecha_compra: today,
        descuento: 0,
        monto_pagado: 0,
        metodo_pago: "efectivo",
        referencia: "",
        observaciones: "",
        detalles: [{ id_producto: "", cantidad: 1, precio_unitario: 0 }],
    };
}
function addDetalle() {
    form.value.detalles.push({
        id_producto: "",
        cantidad: 1,
        precio_unitario: 0,
    });
}
function removeDetalle(index) {
    if (form.value.detalles.length > 1) form.value.detalles.splice(index, 1);
}
function setPrecioProducto(detalle) {
    const producto = props.productos.find(
        (p) => Number(p.id) === Number(detalle.id_producto),
    );
    if (producto)
        detalle.precio_unitario = Number(
            producto.precio_compra || producto.precio || 0,
        );
}
function storeCompra() {
    router.post(route("compras.store"), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAdd.value = false;
            resetForm();
        },
    });
}
function aprobarCompra() {
    if (!showApprove.value) return;
    router.put(
        route("compras.aprobar", showApprove.value.id),
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
function ejecutarCompra() {
    if (!showApprove.value) return;
    router.put(
        route("compras.ejecutar", showApprove.value.id),
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
function rechazarCompra() {
    if (!showReject.value) return;
    router.put(
        route("compras.rechazar", showReject.value.id),
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
function deleteCompra(id) {
    router.delete(route("compras.destroy", id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = null;
        },
    });
}
function badgeClass(estado) {
    return estado === "pagado"
        ? "badge badge-success"
        : estado === "parcial"
          ? "badge badge-warning"
          : estado === "pendiente_aprobacion"
            ? "badge badge-info"
            : estado === "rechazada" || estado === "anulado"
              ? "badge badge-danger"
              : "badge badge-secondary";
}
function badgeAprobacionClass(estado) {
    return estado === "aprobada"
        ? "badge badge-success"
        : estado === "pendiente"
          ? "badge badge-warning"
          : estado === "rechazada"
            ? "badge badge-danger"
            : estado === "anulada"
              ? "badge badge-secondary"
              : "badge badge-info";
}
function puedeAnular(compra) {
    if (!canDelete.value) return false;
    if (
        compra.estado_aprobacion === "pendiente" ||
        compra.estado_aprobacion === "rechazada"
    )
        return true;
    return (
        compra.estado_aprobacion === "aprobada" &&
        Number(compra.monto_pagado || 0) === 0
    );
}
</script>

<template>
    <AppLayout title="Compras">
        <section class="content" v-if="can('Compra')">
            <div
                class="card-header d-flex justify-content-between align-items-center"
            >
                <div>
                    <h1 class="card-title mb-0">
                        <i class="fas fa-shopping-basket mr-2"></i
                        ><b>COMPRAS</b>
                    </h1>
                    <!-- <small class="text-muted"
                        >Inventario solicita compras; Administración aprueba o
                        rechaza antes de afectar stock.</small
                    > -->
                </div>
                <button
                    v-if="canAdd"
                    class="btn btn-success"
                    @click="showAdd = true"
                >
                    <i class="fa fa-plus"></i>&nbsp; Solicitar compra
                </button>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h4>{{ pendientes.length }}</h4>
                            <p>Pendientes de aprobación</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4>{{ aprobadas.length }}</h4>
                            <p>Compras aprobadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h4>{{ rechazadas.length }}</h4>
                            <p>Solicitudes rechazadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4>{{ money(totalAprobado) }}</h4>
                            <p>Total aprobado</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card table-responsive">
                <div class="card-body">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>FECHA</th>
                                <th>PROVEEDOR</th>
                                <th>SOLICITADO POR</th>
                                <th>TOTAL</th>
                                <!-- <th>PAGADO PROP.</th> -->
                                <!-- <th>SALDO</th> -->
                                <th>ESTADO</th>
                                <!-- <th>ESTADO PAGO</th> -->
                                <th>DET.</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="compra in compras" :key="compra.id">
                                <td>{{ compra.id }}</td>
                                <td>{{ formatDate(compra.fecha_compra) }}</td>
                                <td>{{ compra.proveedor?.nombre || "-" }}</td>
                                <td>
                                    {{
                                        compra.solicitante?.nombre ||
                                        compra.usuario?.nombre ||
                                        "-"
                                    }}
                                </td>
                                <td>{{ money(compra.total) }}</td>
                                <!-- <td>{{ money(compra.monto_pagado) }}</td> -->
                                <!-- <td>{{ money(compra.saldo) }}</td> -->
                                <td>
                                    <span
                                        :class="
                                            badgeAprobacionClass(
                                                compra.estado_aprobacion,
                                            )
                                        "
                                        >{{
                                            compra.estado_aprobacion ||
                                            "aprobada"
                                        }}</span
                                    >
                                </td>
                                <!-- <td>
                                    <span :class="badgeClass(compra.estado)">{{
                                        compra.estado
                                    }}</span>
                                </td> -->
                                <td>
                                    {{ compra.detalles?.length || 0 }} ítems
                                </td>
                                <td class="text-nowrap">
                                    <Link
                                        :href="route('compras.show', compra.id)"
                                        class="btn btn-xs btn-outline-primary mr-1"
                                        title="Ver"
                                        ><i class="fa fa-eye"></i
                                    ></Link>
                                    <button
                                        v-if="
                                            canApprove &&
                                            compra.estado_aprobacion ===
                                                'aprobada'
                                        "
                                        class="btn btn-xs btn-success mr-1"
                                        @click="showApprove = compra"
                                        title="Ejecutar"
                                    >
                                        <i class="fa fa-check"></i>
                                    </button>
                                    <button
                                        v-if="
                                            canApprove &&
                                            compra.estado_aprobacion ===
                                                'pendiente'
                                        "
                                        class="btn btn-xs btn-danger mr-1"
                                        @click="showReject = compra"
                                        title="Rechazar"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
                                    <button
                                        v-if="puedeAnular(compra)"
                                        class="btn btn-xs btn-outline-danger"
                                        @click="showDelete = compra"
                                        title="Anular"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="compras.length === 0">
                                <td colspan="11" class="text-center text-muted">
                                    No hay solicitudes ni compras registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showAdd" class="modal-mask">
                <div class="modal-container modal-xl-custom">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Solicitar compra a proveedor
                        </h5>
                        <button
                            class="btn-close"
                            @click="showAdd = false"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            Esta acción registra una <b>solicitud pendiente</b>.
                            El stock y el pago al proveedor se aplicarán recién
                            cuando el administrador apruebe la compra.
                        </div>
                        <div class="row">
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
                                <label>Fecha requerida</label
                                ><input
                                    type="date"
                                    class="form-control"
                                    v-model="form.fecha_compra"
                                />
                                <p
                                    v-if="errors.fecha_compra"
                                    class="text-danger"
                                >
                                    {{ errors.fecha_compra }}
                                </p>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Observaciones</label
                                ><input
                                    class="form-control"
                                    v-model="form.observaciones"
                                    placeholder="Motivo o necesidad de compra"
                                />
                                <p
                                    v-if="errors.observaciones"
                                    class="text-danger"
                                >
                                    {{ errors.observaciones }}
                                </p>
                            </div>
                        </div>

                        <h6 class="mt-3">
                            <b>Detalle de productos solicitados</b>
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th width="110">Cantidad</th>
                                        <th width="150">Precio compra</th>
                                        <th width="150">Subtotal</th>
                                        <th width="60"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            detalle, index
                                        ) in form.detalles"
                                        :key="index"
                                    >
                                        <td>
                                            <select
                                                class="form-control"
                                                v-model="detalle.id_producto"
                                                @change="
                                                    setPrecioProducto(detalle)
                                                "
                                            >
                                                <option value="">
                                                    Seleccione
                                                </option>
                                                <option
                                                    v-for="producto in productos"
                                                    :key="producto.id"
                                                    :value="producto.id"
                                                >
                                                    {{ producto.nombre }} |
                                                    stock: {{ producto.stock }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                min="1"
                                                class="form-control"
                                                v-model.number="
                                                    detalle.cantidad
                                                "
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="
                                                    detalle.precio_unitario
                                                "
                                            />
                                        </td>
                                        <td>
                                            {{
                                                money(
                                                    Number(
                                                        detalle.cantidad || 0,
                                                    ) *
                                                        Number(
                                                            detalle.precio_unitario ||
                                                                0,
                                                        ),
                                                )
                                            }}
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-danger"
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
                        </div>
                        <button
                            class="btn btn-outline-primary btn-sm"
                            @click="addDetalle"
                        >
                            <i class="fa fa-plus"></i> Agregar producto
                        </button>
                        <p v-if="errors.detalles" class="text-danger mt-2">
                            {{ errors.detalles }}
                        </p>

                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label>Subtotal</label
                                ><input
                                    class="form-control"
                                    :value="money(subtotal)"
                                    disabled
                                />
                            </div>
                            <div class="col-md-3">
                                <label>Descuento</label
                                ><input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    v-model.number="form.descuento"
                                />
                                <p v-if="errors.descuento" class="text-danger">
                                    {{ errors.descuento }}
                                </p>
                            </div>
                            <div class="col-md-3">
                                <label>Total</label
                                ><input
                                    class="form-control"
                                    :value="money(total)"
                                    disabled
                                />
                            </div>
                            <div class="col-md-3">
                                <label>Saldo estimado</label
                                ><input
                                    class="form-control"
                                    :value="money(saldo)"
                                    disabled
                                />
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4">
                                <label>Pago inicial propuesto</label
                                ><input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    v-model.number="form.monto_pagado"
                                />
                                <p
                                    v-if="errors.monto_pagado"
                                    class="text-danger"
                                >
                                    {{ errors.monto_pagado }}
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
                            <div class="col-md-4">
                                <label>Referencia propuesta</label
                                ><input
                                    class="form-control"
                                    v-model="form.referencia"
                                    placeholder="Cotización, proforma o comprobante"
                                />
                                <p v-if="errors.referencia" class="text-danger">
                                    {{ errors.referencia }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showAdd = false"
                        >
                            Cancelar</button
                        ><button class="btn btn-primary" @click="storeCompra">
                            Enviar solicitud
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showApprove" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Ejecutar compra</h5>
                        <button
                            class="btn-close"
                            @click="showApprove = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            ¿Ejecutar la compra Nº
                            <b>{{ showApprove.id }}</b> por
                            {{ money(showApprove.total) }}?
                        </p>
                        <div class="alert alert-warning py-2">
                            Al ejecutar, el sistema actualizará stock, movimiento
                            de inventario y pago.
                        </div>
                        <label>Observación de ejecución</label>
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
                        <button class="btn btn-success" @click="ejecutarCompra">
                            Aprobar y ejecutar compra
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
                        ><button class="btn btn-danger" @click="rechazarCompra">
                            Rechazar solicitud
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5>Anular compra o solicitud</h5>
                        <button
                            class="btn-close"
                            @click="showDelete = null"
                        ></button>
                    </div>
                    <div class="modal-body">
                        ¿Anular el registro Nº <b>{{ showDelete.id }}</b
                        >?
                        <div
                            v-if="showDelete.estado_aprobacion === 'aprobada'"
                            class="alert alert-warning mt-2 mb-0"
                        >
                            Si la compra fue aprobada y el stock no fue usado,
                            se revertirá el inventario.
                        </div>
                        <div v-else class="alert alert-info mt-2 mb-0">
                            Como la solicitud no fue aprobada, no se afectará el
                            stock.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-secondary"
                            @click="showDelete = null"
                        >
                            Cancelar</button
                        ><button
                            class="btn btn-danger"
                            @click="deleteCompra(showDelete.id)"
                        >
                            Anular
                        </button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver compras." />
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
.btn-xs {
    padding: 0.125rem 0.35rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}
</style>
