<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import CreateUserModal from "./Create.vue";
import EditUserModal from "./Edit.vue";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    usuarios: Array,
    roles: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Usuario" && p.state === "a" && p[type] === true
    );
}

const can = (funcionalidad) => {
    return page.props.auth.privilegios?.[funcionalidad]?.leer;
};

const canAdd = computed(() => hasPermission("agregar"));
const canEdit = computed(() => hasPermission("modificar"));
const canDelete = computed(() => hasPermission("borrar"));

const showCreate = ref(false);
const userToEdit = ref(null);
const userToDelete = ref(null);

function deleteUsuario() {
    if (!userToDelete.value) return;
    router.delete(route("usuario.destroy", userToDelete.value.id), {
        onSuccess: () => userToDelete.value = null
    });
}

let dataTable = null;
onMounted(() => {
    if (window.$) {
        dataTable = window.$("#usuarios").DataTable({
            responsive: true, lengthChange: true, autoWidth: false,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
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
    <AppLayout title="G. Usuarios">
        <section class="content" v-if="can('Usuario')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h1 class="card-title mb-0"><i class="fas fa-clock mr-2"></i><b>GESTIONAR USUARIOS</b></h1>
                <div class="d-flex align-items-center ml-auto">
                    <button v-if="canAdd" class="btn btn-success" @click="showCreate = true">
                        <i class="fa fa-plus"></i>&nbsp; Agregar
                    </button>
                </div>
            </div>

            <div class="card table-responsive mt-3">
                <div class="card-body">
                    <table class="table table-hover" id="usuarios">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE</th>
                                <th>EMAIL</th>
                                <th>ROL</th>
                                <th>TELEFONO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="usuario in usuarios" :key="usuario.id">
                                <td>{{ usuario.id }}</td>
                                <td>{{ usuario.nombre }}</td>
                                <td>{{ usuario.email }}</td>
                                <td>{{ usuario.rol.nombre }}</td>
                                <td>{{ usuario.telefono }}</td>
                                <td>
                                    <a v-if="canEdit" href="#" @click.prevent="userToEdit = usuario">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a v-if="canDelete" href="#" @click.prevent="userToDelete = usuario">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <CreateUserModal :show="showCreate" :roles="roles" @close="showCreate = false" />

            <EditUserModal v-if="userToEdit" :show="!!userToEdit" :usuario="userToEdit" :roles="roles"
                @close="userToEdit = null" />

            <div v-if="userToDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Eliminar Usuario?</h5>
                    <p>¿Deseas eliminar a <strong>{{ userToDelete.nombre }}</strong>?</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="userToDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deleteUsuario">Eliminar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permisos para ver los usuarios." />

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
    width: min(400px, 90%);
    border-radius: 8px;
    padding: 20px;
}
</style>