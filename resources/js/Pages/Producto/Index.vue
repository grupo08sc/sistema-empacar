<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    productos: { type: Array, default: () => [] },
    num: Number,
    categorias: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const errors = computed(() => page.props.errors || {});

function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Producto" && p.state === "a" && p[type] === true
    );
}

const can = (funcionalidad) => Boolean(page.props.auth.privilegios?.[funcionalidad]?.leer);
const canAdd = computed(() => hasPermission("agregar"));
const canEdit = computed(() => hasPermission("modificar"));
const canDelete = computed(() => hasPermission("borrar"));

const productoBase = () => ({
    codigo: "",
    nombre: "",
    descripcion: "",
    fecha_ingreso: new Date().toISOString().slice(0, 10),
    precio: 0,
    precio_compra: 0,
    precio_venta: 0,
    stock: 0,
    stock_minimo: 0,
    id_categoria: "",
});

const showAdd = ref(false);
const showEdit = ref(null);
const showDelete = ref(null);
const newProducto = ref(productoBase());
const editProducto = ref(productoBase());

const formatCurrency = (value) => new Intl.NumberFormat("es-BO", {
    style: "currency",
    currency: "BOB",
    minimumFractionDigits: 2,
}).format(Number(value || 0));

function storeProducto() {
    router.post(route("producto.store"), newProducto.value, {
        onSuccess: () => {
            showAdd.value = false;
            newProducto.value = productoBase();
        },
    });
}

function updateProducto() {
    router.put(route("producto.update", editProducto.value.id), editProducto.value, {
        onSuccess: () => showEdit.value = null,
    });
}

function deleteProducto(id) {
    router.delete(route("producto.destroy", id), {
        onSuccess: () => showDelete.value = null,
    });
}

let dataTable = null;
onMounted(() => {
    if (window.$) {
        dataTable = window.$("#productos").DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
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
    <AppLayout title="Productos">
        <section class="content" v-if="can('Producto')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="card-title mb-0"><i class="fas fa-boxes mr-2"></i><b>GESTIONAR PRODUCTOS</b></h1>
                    <small class="text-muted">Inventario comercial de productos, precios y stock.</small>
                </div>
                <button v-if="canAdd" class="btn btn-success ml-auto" @click="showAdd = true">
                    <i class="fa fa-plus"></i>&nbsp; Agregar
                </button>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover" id="productos">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th class="text-end">Compra</th>
                                <th class="text-end">Venta</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Mínimo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="producto in productos" :key="producto.id">
                                <td>{{ producto.id }}</td>
                                <td>{{ producto.codigo || '-' }}</td>
                                <td>{{ producto.nombre }}</td>
                                <td>{{ producto.descripcion || '-' }}</td>
                                <td>{{ producto.categoria?.nombre || 'Sin categoría' }}</td>
                                <td class="text-end">{{ formatCurrency(producto.precio_compra) }}</td>
                                <td class="text-end fw-bold">{{ formatCurrency(producto.precio_venta || producto.precio) }}</td>
                                <td class="text-end">{{ producto.stock }}</td>
                                <td class="text-end">{{ producto.stock_minimo }}</td>
                                <td class="text-nowrap">
                                    <a v-if="canEdit" href="#" @click.prevent="showEdit = producto.id; editProducto = { ...producto }">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a v-if="canDelete" href="#" @click.prevent="showDelete = producto">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showAdd" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Agregar producto</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código</label>
                                <input class="form-control" v-model="newProducto.codigo" />
                                <p v-if="errors.codigo" class="text-danger">{{ errors.codigo }}</p>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nombre</label>
                                <input class="form-control" v-model="newProducto.nombre" />
                                <p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" rows="2" v-model="newProducto.descripcion"></textarea>
                                <p v-if="errors.descripcion" class="text-danger">{{ errors.descripcion }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-control" v-model="newProducto.id_categoria">
                                    <option value="">Sin categoría</option>
                                    <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">{{ categoria.nombre }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de ingreso</label>
                                <input type="date" class="form-control" v-model="newProducto.fecha_ingreso" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio base</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="newProducto.precio" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio compra</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="newProducto.precio_compra" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio venta</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="newProducto.precio_venta" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" v-model.number="newProducto.stock" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock mínimo</label>
                                <input type="number" class="form-control" v-model.number="newProducto.stock_minimo" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showAdd = false">Cancelar</button>
                        <button class="btn btn-primary" @click="storeProducto">Guardar</button>
                    </div>
                </div>
            </div>

            <div v-if="showEdit" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar producto</h5>
                        <button type="button" class="btn-close" @click="showEdit = null"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="form-label">Código</label><input class="form-control" v-model="editProducto.codigo" /></div>
                            <div class="col-md-8 mb-3"><label class="form-label">Nombre</label><input class="form-control" v-model="editProducto.nombre" /></div>
                            <div class="col-md-12 mb-3"><label class="form-label">Descripción</label><textarea class="form-control" rows="2" v-model="editProducto.descripcion"></textarea></div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-control" v-model="editProducto.id_categoria">
                                    <option value="">Sin categoría</option>
                                    <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">{{ categoria.nombre }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label">Fecha ingreso</label><input type="date" class="form-control" v-model="editProducto.fecha_ingreso" /></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Precio base</label><input type="number" step="0.01" class="form-control" v-model.number="editProducto.precio" /></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Precio compra</label><input type="number" step="0.01" class="form-control" v-model.number="editProducto.precio_compra" /></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Precio venta</label><input type="number" step="0.01" class="form-control" v-model.number="editProducto.precio_venta" /></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Stock</label><input type="number" class="form-control" v-model.number="editProducto.stock" /></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Stock mínimo</label><input type="number" class="form-control" v-model.number="editProducto.stock_minimo" /></div>
                        </div>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showEdit = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updateProducto">Guardar cambios</button>
                    </div>
                </div>
            </div>

            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Desactivar producto?</h5>
                    <p>Se desactivará <strong>{{ showDelete.nombre }}</strong>.</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deleteProducto(showDelete.id)">Desactivar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver productos." />
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
    width: min(850px, 95vw);
    max-height: 92vh;
    overflow-y: auto;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,.25);
}
</style>
