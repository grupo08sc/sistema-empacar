<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    inventarios: Array,
    num: Number,
    productos: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const errors = computed(() => page.props.errors);

function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Inventario" && p.state === "a" && p[type] === true
    );
}

const can = (funcionalidad) => {
    return page.props.auth.privilegios?.[funcionalidad]?.leer;
};

const canAdd = computed(() => hasPermission("agregar"));
const canEdit = computed(() => hasPermission("modificar"));
const canDelete = computed(() => hasPermission("borrar"));

const showAdd = ref(false);
const showEdit = ref(null);
const showDelete = ref(null);

const newInventario = ref({ cantidad: "", descripcion: "", fecha: "", id_producto: "", tipo: "" });
const editInventario = ref({ id: null, cantidad: "", descripcion: "", fecha: "", id_producto: "", tipo: "" });

function storeInventario() {
    router.post(
        route("inventario.store"),
        newInventario.value,
        {
            onSuccess: () => {
                showAdd.value = false;
                newInventario.value = { cantidad: "", descripcion: "", fecha: "", id_producto: "", tipo: "" };
            },
            onError: (e) => {
                console.log(e);
            }
        }
    );
}

function updateInventario() {
    router.put(
        route("inventario.update", editInventario.value.id),
        editInventario.value,
        {
            onSuccess: () => {
                showEdit.value = null;
            }
        }
    );
}

function deleteInventario(id) {
    router.delete(route("inventario.destroy", id), {
        onSuccess: () => {
            showDelete.value = null;
        }
    });
}

// ---- DATATABLES ----
let dataTable = null;

onMounted(() => {
    if (window.$) {
        dataTable = window.$("#inventarios").DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
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
    <AppLayout title="G. Inventarios">

        <section class="content" v-if="can('Inventario')">
            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center">

                <h1 class="card-title mb-0">
                    <i class="fas fa-clock mr-2"></i><b>GESTIONAR INVENTARIOS</b>
                </h1>

                <div class="d-flex align-items-center ml-auto">
                    <button v-if="canAdd" class="btn btn-success" @click="showAdd = true">
                        <i class="fa fa-plus"></i>&nbsp; Agregar
                    </button>
                </div>

            </div>

            <!-- TABLA -->
            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover" id="inventarios">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>DESCRIPCION</th>
                                <th>FECHA</th>
                                <th>PRODUCTO</th>
                                <th>TIPO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="inventario in inventarios" :key="inventario.id">
                                <td>{{ inventario.id }}</td>
                                <td>{{ inventario.descripcion }}</td>
                                <td>{{ inventario.fecha }}</td>
                                <td>{{ inventario.producto.nombre }}</td>
                                <td>{{ inventario.tipo }}</td>

                                <td>
                                    <a v-if="canEdit" href="#" @click.prevent="
                                        showEdit = inventario.id;
                                    editInventario = { ...inventario };
                                    ">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a v-if="canDelete" href="#" @click.prevent="showDelete = inventario">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL AGREGAR -->
            <div v-if="showAdd" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Agregar Inventario</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <input class="form-control" v-model="newInventario.descripcion" placeholder="Descripción" />
                            <p v-if="errors.descripcion" class="text-danger">{{ errors.descripcion }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" v-model="newInventario.cantidad"
                                placeholder="Cantidad" />
                            <p v-if="errors.cantidad" class="text-danger">{{ errors.cantidad }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" v-model="newInventario.fecha" placeholder="Fecha" />
                            <p v-if="errors.fecha" class="text-danger">{{ errors.fecha }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" v-model="newInventario.id_producto">
                                <option value="" disabled>Seleccione un producto</option>
                                <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                                    {{ producto.nombre }}
                                </option>
                            </select>
                            <p v-if="errors.id_producto" class="text-danger">{{ errors.id_producto }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" v-model="newInventario.tipo">
                                <option value="" disabled>Seleccione un tipo</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="producto terminado">Producto terminado</option>
                            </select>
                            <p v-if="errors.tipo" class="text-danger">{{ errors.tipo }}</p>
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showAdd = false">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="storeInventario">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>


            <!-- MODAL EDITAR -->
            <div v-if="showEdit" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar Rol</h5>
                        <button type="button" class="btn-close" @click="showEdit = null"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <input class="form-control" v-model="editInventario.descripcion"
                                placeholder="Descripción" />
                            <p v-if="errors.descripcion" class="text-danger">{{ errors.descripcion }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" v-model="editInventario.cantidad"
                                placeholder="Cantidad" />
                            <p v-if="errors.cantidad" class="text-danger">{{ errors.cantidad }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" v-model="editInventario.fecha"
                                placeholder="Fecha" />
                            <p v-if="errors.fecha" class="text-danger">{{ errors.fecha }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" v-model="editInventario.id_producto">
                                <option value="" disabled>Seleccione un producto</option>
                                <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                                    {{ producto.nombre }}
                                </option>
                            </select>
                            <p v-if="errors.id_producto" class="text-danger">{{ errors.id_producto }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" v-model="editInventario.tipo">
                                <option value="" disabled>Seleccione un tipo</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="producto terminado">Producto terminado</option>
                            </select>
                            <p v-if="errors.tipo" class="text-danger">{{ errors.tipo }}</p>
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEdit = null">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="updateInventario">
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR -->
            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">¿Eliminar Inventario?</h5>
                        <button type="button" class="btn-close" @click="showDelete = null"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el inventario <strong>{{ showDelete.nombre }}</strong>?
                        </p>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">
                            Cancelar
                        </button>
                        <button class="btn btn-danger" @click="deleteInventario(showDelete)">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver los inventarios." />

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
}

.modal-container {
    background: white;
    width: min(500px, 90%);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
}
</style>
