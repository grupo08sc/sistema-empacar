<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import NoPermiso from "@/Components/NoPermiso.vue";

const props = defineProps({
    roles: Array,
    funcionalidades: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// --- Permisos del usuario logueado ---
function hasPermission(type) {
    return user.value?.rol?.privilegios?.some(
        (p) => p.funcionalidad === "Privilegio" && p.state === 'a' && p[type] === true
    );
}
const can = (funcionalidad) => {
    return page.props.auth.privilegios?.[funcionalidad]?.leer;
};

const canAdd = computed(() => hasPermission("agregar"));
const canEdit = computed(() => hasPermission("modificar"));
const canDelete = computed(() => hasPermission("borrar"));

// --- Modal control ---
const showAdd = ref(false);
const showEdit = ref(null);
const showDelete = ref(null);

// --- Nuevo privilegio ---
const newPrivilegio = ref({ rol_id: "", funcion: "", agregar: false, borrar: false, modificar: false, leer: false });

// --- Editar privilegios ---
function updatePrivilegio() {
    router.put(
        route("privilegios.update", showEdit.value.id),
        { privilegios: showEdit.value.privilegiosAgrupados },
        {
            onSuccess: () => showEdit.value = null,
            onError: (e) => console.log(e)
        }
    );
}

// --- Eliminar privilegio (ejemplo) ---
function deletePrivilegio() {
    if (!showDelete.value) return;
    router.delete(route("privilegios.destroyByRol", showDelete.value.id), {
        onSuccess: () => showDelete.value = null
    });
}

function storePrivilegio() {
    router.post(route("privilegio.store"), newPrivilegio.value, {
        onSuccess: () => {
            showAdd.value = false;
            newPrivilegio.value = { rol_id: "", funcion: "", agregar: false, borrar: false, modificar: false, leer: false, estado: 'a' };
        },
        onError: (e) => console.log(e)
    });
}

</script>

<template>

    <Head title="G. Privilegios" />
    <AppLayout>

        <section class="content" v-if="can('Privilegio')">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h1 class="card-title mb-0"><b>Gestiónar Privilegios</b></h1>
                <div class="d-flex align-items-center ml-auto">
                    <button v-if="canAdd" class="btn btn-success" @click="showAdd = true">
                        <i class="fa fa-plus"></i>&nbsp; Agregar
                    </button>
                </div>
            </div>

            <div class="card table-responsive">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Privilegios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rol in roles" :key="rol.id">
                                <td>{{ rol.nombre }}</td>
                                <td>
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Funcionalidad</th>
                                                <th>Agregar</th>
                                                <th>Borrar</th>
                                                <th>Modificar</th>
                                                <th>Leer</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="p in rol.privilegiosAgrupados" :key="p.id">
                                                <td>{{ p.funcionalidad }}</td>
                                                <td><i
                                                        :class="p.agregar ? 'fa fa-check text-success' : 'fa fa-times text-danger'"></i>
                                                </td>
                                                <td><i
                                                        :class="p.borrar ? 'fa fa-check text-success' : 'fa fa-times text-danger'"></i>
                                                </td>
                                                <td><i
                                                        :class="p.modificar ? 'fa fa-check text-success' : 'fa fa-times text-danger'"></i>
                                                </td>
                                                <td><i
                                                        :class="p.leer ? 'fa fa-check text-success' : 'fa fa-times text-danger'"></i>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>
                                    <a v-if="canEdit" href="#"
                                        @click.prevent="showEdit = JSON.parse(JSON.stringify(rol))">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a v-if="canDelete" href="#" class="ms-2" @click.prevent="showDelete = rol">
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
                        <h5 class="modal-title">Agregar Privilegio</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mt-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" v-model="newPrivilegio.rol_id">
                                <option value="" disabled>Seleccione un rol</option>
                                <option v-for="rol in roles" :key="rol.id" :value="rol.id">{{ rol.nombre }}</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Funcionalidad</label>
                            <select class="form-select" v-model="newPrivilegio.funcion">
                                <option value="" disabled>Seleccione una funcionalidad</option>
                                <option v-for="func in funcionalidades" :key="func" :value="func">{{ func }}</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label>Privilegios</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="newPrivilegio.agregar" />
                                <label class="form-check-label">Agregar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="newPrivilegio.borrar" />
                                <label class="form-check-label">Borrar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="newPrivilegio.modificar" />
                                <label class="form-check-label">Modificar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="newPrivilegio.leer" />
                                <label class="form-check-label">Leer</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer text-end mt-3">
                        <button class="btn btn-secondary" @click="showAdd = false">Cancelar</button>
                        <button class="btn btn-primary" @click="storePrivilegio">Guardar</button>
                    </div>
                </div>
            </div>


            <!-- MODAL EDITAR -->
            <div v-if="showEdit" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar privilegios del rol: {{ showEdit.nombre }}</h5>
                        <button type="button" class="btn-close" @click="showEdit = null"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Funcionalidad</th>
                                    <th>Agregar</th>
                                    <th>Borrar</th>
                                    <th>Modificar</th>
                                    <th>Leer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="func in showEdit.privilegiosAgrupados" :key="func.id">
                                    <td>{{ func.funcionalidad }}</td>
                                    <td><input type="checkbox" v-model="func.agregar" /></td>
                                    <td><input type="checkbox" v-model="func.borrar" /></td>
                                    <td><input type="checkbox" v-model="func.modificar" /></td>
                                    <td><input type="checkbox" v-model="func.leer" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showEdit = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updatePrivilegio">Guardar Cambios</button>
                    </div>
                </div>
            </div>

            <!-- MODAL ELIMINAR -->
            <div v-if="showDelete" class="modal-mask">
                <div class="modal-container text-center">
                    <h5>¿Desactivar privilegios?</h5>
                    <p>Se desactivarán todos los privilegios del rol <strong>{{ showDelete.nombre }}</strong>.</p>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" @click="showDelete = null">Cancelar</button>
                        <button class="btn btn-danger" @click="deletePrivilegio">Desactivar</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver los privilegios." />

    </AppLayout>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9999;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.35);
}

.modal-container {
    background: white;
    width: min(600px, 90%);
    border-radius: 8px;
    padding: 1rem;
}
</style>
