<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    clientes: Array,
    num: Number,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const errors = computed(() => page.props.errors);

function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Cliente" && p.state === "a" && p[type] === true
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

const newCliente = ref({ nombre: "", direccion: "", telefono: "", email: "", documento: "", ciudad: "" });
const editCliente = ref({ id: null, nombre: "", direccion: "", telefono: "", email: "", documento: "", ciudad: "" });

function storeCliente() {
    router.post(
        route("cliente.store"),
        newCliente.value,
        {
            onSuccess: () => {
                showAdd.value = false;
                newCliente.value = { nombre: "", direccion: "", telefono: "", email: "", documento: "", ciudad: "" };
            },
            onError: (e) => {
                console.log(e);
            }
        }
    );
}

function updateCliente() {
    router.put(
        route("cliente.update", editCliente.value.id),
        editCliente.value,
        {
            onSuccess: () => {
                showEdit.value = null;
            }
        }
    );
}

function deleteCliente(id) {
    router.delete(route("cliente.destroy", id), {
        onSuccess: () => {
            showDelete.value = null;
        }
    });
}

// ---- DATATABLES ----
let dataTable = null;

onMounted(() => {
    if (window.$) {
        dataTable = window.$("#clientes").DataTable({
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
    <AppLayout title="G. Cliente">

        <section class="content" v-if="can('Cliente')">
            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center">

                <h1 class="card-title mb-0">
                    <i class="fas fa-clock mr-2"></i><b>GESTIONAR CLIENTES</b>
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
                    <table class="table table-hover" id="clientes">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE</th>
                                <th>DIRECCIÓN</th>
                                <th>TELÉFONO</th>
                                <th>EMAIL</th>
                                <th>DOCUMENTO</th>
                                <th>CIUDAD</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cliente in clientes" :key="cliente.id">
                                <td>{{ cliente.id }}</td>
                                <td>{{ cliente.nombre }}</td>
                                <td>{{ cliente.direccion }}</td>
                                <td>{{ cliente.telefono }}</td>
                                <td>{{ cliente.email || 'Sin correo' }}</td>
                                <td>{{ cliente.documento || '-' }}</td>
                                <td>{{ cliente.ciudad || '-' }}</td>

                                <td>
                                    <a v-if="canEdit" href="#" @click.prevent="
                                        showEdit = cliente.id;
                                    editCliente = { ...cliente };
                                    ">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a v-if="canDelete" href="#" @click.prevent="showDelete = cliente">
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
                        <h5 class="modal-title">Agregar Cliente</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input class="form-control" v-model="newCliente.nombre" placeholder="Nombre" />
                            <p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input class="form-control" v-model="newCliente.direccion" placeholder="Dirección" />
                            <p v-if="errors.direccion" class="text-danger">{{ errors.direccion }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input class="form-control" v-model="newCliente.telefono" placeholder="Teléfono" />
                            <p v-if="errors.telefono" class="text-danger">{{ errors.telefono }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input class="form-control" v-model="newCliente.email" placeholder="Email" />
                            <p v-if="errors.email" class="text-danger">{{ errors.email }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="documento" class="form-label">Documento</label>
                            <input class="form-control" v-model="newCliente.documento" placeholder="CI/NIT" />
                            <p v-if="errors.documento" class="text-danger">{{ errors.documento }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="ciudad" class="form-label">Ciudad</label>
                            <input class="form-control" v-model="newCliente.ciudad" placeholder="Ciudad" />
                            <p v-if="errors.ciudad" class="text-danger">{{ errors.ciudad }}</p>
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showAdd = false">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="storeCliente">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>


            <!-- MODAL EDITAR -->
            <div v-if="showEdit" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar Cliente</h5>
                        <button type="button" class="btn-close" @click="showEdit = null"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input class="form-control" v-model="editCliente.nombre" placeholder="Nombre" />
                            <p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input class="form-control" v-model="editCliente.direccion" placeholder="Dirección" />
                            <p v-if="errors.direccion" class="text-danger">{{ errors.direccion }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input class="form-control" v-model="editCliente.telefono" placeholder="Teléfono" />
                            <p v-if="errors.telefono" class="text-danger">{{ errors.telefono }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input class="form-control" v-model="editCliente.email" placeholder="Email" />
                            <p v-if="errors.email" class="text-danger">{{ errors.email }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="documento" class="form-label">Documento</label>
                            <input class="form-control" v-model="editCliente.documento" placeholder="CI/NIT" />
                            <p v-if="errors.documento" class="text-danger">{{ errors.documento }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="ciudad" class="form-label">Ciudad</label>
                            <input class="form-control" v-model="editCliente.ciudad" placeholder="Ciudad" />
                            <p v-if="errors.ciudad" class="text-danger">{{ errors.ciudad }}</p>
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEdit = null">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="updateCliente">
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR -->
            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">¿Eliminar Cliente?</h5>
                        <button type="button" class="btn-close" @click="showDelete = null"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el cliente <strong>{{ showDelete.nombre }}</strong>?</p>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">
                            Cancelar
                        </button>
                        <button class="btn btn-danger" @click="deleteCliente(showDelete.id)">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <NoPermiso v-else mensaje="No tienes permiso para ver los clientes." />

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
