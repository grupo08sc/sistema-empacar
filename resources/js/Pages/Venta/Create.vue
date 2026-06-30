<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps({
    show: Boolean,
    clientes: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);

const productoSeleccionado = ref(null);
const cantidad = ref(1);
const hoy = new Date().toISOString().slice(0, 10);

const form = useForm({
    cliente_id: "",
    detalles: [],
    descuento: 0,
    tipo_pago: "contado",
    monto_inicial: 0,
    cantidad_cuotas: 1,
    frecuencia: "mensual",
    fecha_inicio: hoy,
    metodo_pago_inicial: "efectivo",
    referencia_inicial: "",
    observaciones: "",
});

const subtotal = computed(() => form.detalles.reduce((acc, item) => acc + Number(item.subtotal || 0), 0));
const descuento = computed(() => Math.max(0, Number(form.descuento || 0)));
const totalGeneral = computed(() => Math.max(0, subtotal.value - descuento.value));
const montoInicial = computed(() => Math.min(totalGeneral.value, Math.max(0, Number(form.monto_inicial || 0))));
const saldo = computed(() => Math.max(0, totalGeneral.value - montoInicial.value));
const cuotaEstimada = computed(() => {
    const n = Math.max(1, Number(form.cantidad_cuotas || 1));
    return saldo.value > 0 ? saldo.value / n : 0;
});

watch(totalGeneral, (total) => {
    if (form.tipo_pago === "contado") {
        form.monto_inicial = Number(total.toFixed(2));
    }
    if (Number(form.monto_inicial || 0) > total) {
        form.monto_inicial = Number(total.toFixed(2));
    }
});

watch(() => form.tipo_pago, (tipo) => {
    if (tipo === "contado") {
        form.monto_inicial = Number(totalGeneral.value.toFixed(2));
        form.cantidad_cuotas = 0;
    }
    if (tipo === "credito") {
        form.monto_inicial = 0;
        form.cantidad_cuotas = Math.max(1, Number(form.cantidad_cuotas || 1));
    }
    if (tipo === "mixto") {
        form.cantidad_cuotas = Math.max(1, Number(form.cantidad_cuotas || 1));
    }
});

const formatCurrency = (value) => new Intl.NumberFormat("es-BO", {
    style: "currency",
    currency: "BOB",
    minimumFractionDigits: 2,
}).format(Number(value || 0));

const precioProducto = (producto) => Number(producto?.precio_venta ?? producto?.precio ?? 0);

const agregarDetalle = () => {
    if (!productoSeleccionado.value) {
        alert("Seleccione un producto.");
        return;
    }

    const cant = Number(cantidad.value || 0);
    if (cant < 1) {
        alert("La cantidad debe ser mayor a 0.");
        return;
    }

    const producto = productoSeleccionado.value;
    const stock = Number(producto.stock ?? 0);

    if (stock < cant) {
        alert(`Stock insuficiente. Disponible: ${stock}.`);
        return;
    }

    const precio = precioProducto(producto);
    form.detalles.push({
        tipo: "Producto",
        producto_id: producto.id,
        nombre: producto.nombre,
        cantidad: cant,
        precio,
        subtotal: Number((precio * cant).toFixed(2)),
        stock_disponible: stock,
    });

    productoSeleccionado.value = null;
    cantidad.value = 1;
};

const eliminarDetalle = (index) => form.detalles.splice(index, 1);

const limpiarFormulario = () => {
    form.reset();
    form.detalles = [];
    form.descuento = 0;
    form.tipo_pago = "contado";
    form.monto_inicial = 0;
    form.cantidad_cuotas = 1;
    form.frecuencia = "mensual";
    form.fecha_inicio = hoy;
    form.metodo_pago_inicial = "efectivo";
    form.referencia_inicial = "";
    form.observaciones = "";
    productoSeleccionado.value = null;
    cantidad.value = 1;
};

const cerrar = () => {
    limpiarFormulario();
    emit("close");
};

const submit = () => {
    if (!form.cliente_id) {
        alert("Seleccione un cliente.");
        return;
    }
    if (form.detalles.length === 0) {
        alert("Debe agregar al menos un producto.");
        return;
    }
    if (totalGeneral.value <= 0) {
        alert("El total de la venta debe ser mayor a 0.");
        return;
    }
    if (Number(form.monto_inicial || 0) > totalGeneral.value) {
        alert("El monto inicial no puede ser mayor al total de la venta.");
        return;
    }
    if (["credito", "mixto"].includes(form.tipo_pago) && saldo.value > 0 && Number(form.cantidad_cuotas || 0) < 1) {
        alert("Debe indicar la cantidad de cuotas para una venta a crédito o mixta.");
        return;
    }
    if (form.tipo_pago === "contado") {
        form.monto_inicial = Number(totalGeneral.value.toFixed(2));
        form.cantidad_cuotas = 0;
    }

    form.post(route("venta.store"), {
        preserveScroll: true,
        onSuccess: () => cerrar(),
    });
};
</script>

<template>
    <div v-if="show" class="modal-mask">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <div>
                        <h5 class="modal-title mb-0">Nueva venta</h5>
                        <small>Registro comercial al contado, crédito o pago mixto.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="cerrar"></button>
                </div>

                <form @submit.prevent="submit">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cliente</label>
                                <select v-model="form.cliente_id" class="form-control" required>
                                    <option value="">Seleccionar cliente</option>
                                    <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                        {{ cliente.nombre }} {{ cliente.apellido || "" }} {{ cliente.documento ? `- ${cliente.documento}` : "" }}
                                    </option>
                                </select>
                                <div v-if="form.errors.cliente_id" class="text-danger small">{{ form.errors.cliente_id }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Observaciones</label>
                                <input v-model="form.observaciones" type="text" class="form-control" placeholder="Observaciones internas de la venta">
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">Detalle de productos</h6>
                        <div class="row mb-3 bg-light p-3 rounded mx-1">
                            <div class="col-md-7">
                                <label class="form-label">Producto</label>
                                <select v-model="productoSeleccionado" class="form-control">
                                    <option :value="null">Seleccionar producto</option>
                                    <option v-for="p in productos" :key="p.id" :value="p">
                                        {{ p.nombre }} — {{ formatCurrency(precioProducto(p)) }} — Stock: {{ p.stock ?? 0 }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" v-model.number="cantidad" min="1" class="form-control">
                            </div>

                            <div class="col-md-3 align-self-end">
                                <button type="button" class="btn btn-success w-100" @click="agregarDetalle">
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Producto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">P/U</th>
                                        <th class="text-end">Subtotal</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(detalle, index) in form.detalles" :key="index">
                                        <td><span class="badge bg-secondary">{{ detalle.tipo }}</span></td>
                                        <td>{{ detalle.nombre }}</td>
                                        <td class="text-end">{{ detalle.cantidad }}</td>
                                        <td class="text-end">{{ formatCurrency(detalle.precio) }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(detalle.subtotal) }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm" @click="eliminarDetalle(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.detalles.length === 0">
                                        <td colspan="6" class="text-center text-muted py-4">Todavía no hay productos agregados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-lg-5 ms-auto">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal</span>
                                            <strong>{{ formatCurrency(subtotal) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Descuento</span>
                                            <input v-model.number="form.descuento" type="number" min="0" step="0.01" class="form-control form-control-sm text-end" style="max-width: 160px;">
                                        </div>
                                        <div class="d-flex justify-content-between fs-5 border-top pt-2">
                                            <span>Total</span>
                                            <strong>{{ formatCurrency(totalGeneral) }}</strong>
                                        </div>
                                        <div v-if="form.errors.descuento" class="text-danger small mt-1">{{ form.errors.descuento }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">Condición de pago</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tipo de pago</label>
                                <select v-model="form.tipo_pago" class="form-control" required>
                                    <option value="contado">Contado</option>
                                    <option value="credito">Crédito / cuotas</option>
                                    <option value="mixto">Mixto</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Pago inicial</label>
                                <input v-model.number="form.monto_inicial" type="number" min="0" step="0.01" class="form-control" :readonly="form.tipo_pago === 'contado'">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Método pago inicial</label>
                                <select v-model="form.metodo_pago_inicial" class="form-control" :disabled="Number(form.monto_inicial || 0) <= 0">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="qr">QR</option>
                                    <option value="PagoFacil">PagoFácil</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Referencia</label>
                                <input v-model="form.referencia_inicial" type="text" class="form-control" placeholder="Nro. transacción, recibo, etc.">
                            </div>
                        </div>

                        <div v-if="form.tipo_pago !== 'contado' && saldo > 0" class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label class="form-label">N.º de cuotas</label>
                                <input v-model.number="form.cantidad_cuotas" type="number" min="1" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Frecuencia</label>
                                <select v-model="form.frecuencia" class="form-control">
                                    <option value="semanal">Semanal</option>
                                    <option value="quincenal">Quincenal</option>
                                    <option value="mensual">Mensual</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Fecha primera cuota</label>
                                <input v-model="form.fecha_inicio" type="date" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Cuota estimada</label>
                                <input :value="formatCurrency(cuotaEstimada)" type="text" class="form-control fw-bold" readonly>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            <div class="row text-center">
                                <div class="col-md-4"><strong>Total:</strong> {{ formatCurrency(totalGeneral) }}</div>
                                <div class="col-md-4"><strong>Pagado inicial:</strong> {{ formatCurrency(montoInicial) }}</div>
                                <div class="col-md-4"><strong>Saldo:</strong> {{ formatCurrency(saldo) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" @click="cerrar">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                            Guardar venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 1050;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    padding: 2rem 1rem;
}
.modal-dialog {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}
.modal-content {
    position: relative;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, .2);
    border-radius: .5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
}
</style>
