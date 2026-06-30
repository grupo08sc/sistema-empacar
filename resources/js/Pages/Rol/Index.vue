<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    roles: Array,
    num: Number,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const errors = computed(() => page.props.errors);

function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Rol" && p.state === "a" && p[type] === true
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

const newRol = ref({ nombre: "" });
const editRol = ref({ id: null, nombre: "" });

function storeRol() {
    router.post(
        route("rol.store"),
        newRol.value,
        {
            onSuccess: () => {
                showAdd.value = false;
                newRol.value.nombre = "";
            },
            onError: (e) => {
                console.log(e);
            }
        }
    );
}

function updateRol() {
    router.put(
        route("rol.update", editRol.value.id),
        editRol.value,
        {
            onSuccess: () => {
                showEdit.value = null;
            }
        }
    );
}

function deleteRol(id) {
    router.delete(route("rol.destroy", id), {
        onSuccess: () => {
            showDelete.value = null;
        }
    });
}

let dataTable = null;

onMounted(() => {
    if (window.$) {
        dataTable = window.$("#roles").DataTable({
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
    <AppLayout title="G. Rol">

        <section class="content" v-if="can('Rol')">
            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center">

                <h1 class="card-title mb-0">
                    <i class="fas fa-clock mr-2"></i><b>GESTIONAR ROLES</b>
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
                    <table class="table table-hover" id="roles">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rol in roles" :key="rol.id">
                                <td>{{ rol.id }}</td>
                                <td>{{ rol.nombre }}</td>
                                <td>{{ rol.state === 'a' ? 'Activo' : 'Inactivo' }}</td>
                                <td>
                                    <a v-if="canEdit" href="#" @click.prevent="
                                        showEdit = rol.id;
                                    editRol = { ...rol };
                                    ">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a v-if="canDelete" href="#" @click.prevent="showDelete = rol">
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
                        <h5 class="modal-title">Agregar Rol</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>
                    <div class="modal-body">
                        <div>
                            <label for="nombre" class="form-label">Nombre</label>
                            <input class="form-control" v-model="newRol.nombre" placeholder="Nombre" />
                            <p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p>
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showAdd = false">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="storeRol">
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
                        <div>
                            <label for="nombre" class="form-label">Nombre</label>
                            <input class="form-control" v-model="editRol.nombre" placeholder="Nombre" />
                        </div>
                    </div>

                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEdit = null">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" @click="updateRol">
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR -->
            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">¿Eliminar Rol?</h5>
                        <button type="button" class="btn-close" @click="showDelete = null"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el rol <strong>{{ showDelete.nombre }}</strong>?</p>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">
                            Cancelar
                        </button>
                        <button class="btn btn-danger" @click="deleteRol(showDelete)">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver los roles." />
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
