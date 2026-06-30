<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import NoPermiso from "@/Components/NoPermiso.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    datos: Object,
});

const page = usePage();
const privilegios = computed(() => page.props.auth?.privilegios || {});

const can = (funcionalidad) => {
    return Boolean(privilegios.value?.[funcionalidad]?.leer);
};

const canEdit = computed(() => Boolean(privilegios.value?.Empresa?.modificar));

const showEditNombre = ref(null);
const showEditDireccion = ref(null);
const showEditCorreo = ref(null);
const showEditTelefono = ref(null);
const nombreEmpresa = ref(props.datos?.nombre || '');
const direccionEmpresa = ref(props.datos?.direccion || '');
const correoEmpresa = ref(props.datos?.correo || '');
const telefonoEmpresa = ref(props.datos?.telefono || '');
const inputLogo = ref(null);

const logoForm = useForm({
    logo: null,
});

function updateNombre() {
    router.put(
        route("empresa.nombre", props.datos.id),
        { nombre: nombreEmpresa.value },
        {
            onSuccess: () => {
                showEditNombre.value = null;
            }
        }
    );
}

function updateDireccion() {
    router.put(
        route("empresa.direccion", props.datos.id),
        { direccion: direccionEmpresa.value },
        {
            onSuccess: () => {
                showEditDireccion.value = null;
            }
        }
    );
}

function updateCorreo() {
    router.put(
        route("empresa.correo", props.datos.id),
        { correo: correoEmpresa.value },
        {
            onSuccess: () => {
                showEditCorreo.value = null;
            }
        }
    );
}

function updateTelefono() {
    router.put(
        route("empresa.telefono", props.datos.id),
        { telefono: telefonoEmpresa.value },
        {
            onSuccess: () => {
                showEditTelefono.value = null;
            }
        }
    );
}

function seleccionarLogo(event) {
    logoForm.logo = event.target.files?.[0] || null;
}

function actualizarLogo() {
    if (!logoForm.logo) {
        logoForm.setError('logo', 'Debe seleccionar una imagen antes de guardar.');
        return;
    }

    logoForm.post(route('empresa.logo', props.datos.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset('logo');
            if (inputLogo.value) {
                inputLogo.value.value = null;
            }
        },
    });
}

function eliminarLogo() {
    if (!confirm('¿Está seguro de eliminar el logo actual? Se mostrará el logo por defecto.')) {
        return;
    }

    router.delete(route('empresa.logo.eliminar', props.datos.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <section class="content" v-if="can('Empresa')">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h1 class="card-title mb-0">
                        <i class="fas fa-cog mr-1"></i>
                        <b>CONFIGURACIÓN DE EMPRESA</b>
                    </h1>
                </div>

                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img :src="datos.logo_url" alt="Logo actual de la empresa" class="logo-preview">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label text-darkgray font-weight-bold">LOGO DEL SISTEMA</label>
                            <p class="text-muted mb-2">
                                Cargue manualmente el logo que aparecerá en la esquina superior izquierda del sistema.
                            </p>

                            <div class="custom-file mb-2">
                                <input
                                    ref="inputLogo"
                                    type="file"
                                    class="custom-file-input"
                                    id="logoEmpresa"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    :disabled="!canEdit || logoForm.processing"
                                    @change="seleccionarLogo"
                                >
                                <label class="custom-file-label" for="logoEmpresa">
                                    {{ logoForm.logo ? logoForm.logo.name : 'Seleccionar imagen JPG, PNG o WEBP' }}
                                </label>
                            </div>

                            <div v-if="logoForm.errors.logo" class="text-danger small mb-2">
                                {{ logoForm.errors.logo }}
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary btn-sm mr-2"
                                :disabled="!canEdit || logoForm.processing"
                                @click="actualizarLogo"
                            >
                                <i class="fas fa-upload mr-1"></i>
                                Guardar logo
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm"
                                :disabled="!canEdit || logoForm.processing || !datos.logo_path"
                                @click="eliminarLogo"
                            >
                                <i class="fas fa-trash mr-1"></i>
                                Quitar logo
                            </button>

                            <p class="text-muted small mt-2 mb-0">
                                Tamaño máximo: 2 MB. Formatos permitidos: JPG, JPEG, PNG y WEBP.
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="nombre" class="form-label text-darkgray">NOMBRE DE LA EMPRESA</label>
                        <div class="input-group">
                            <label class="form-control-plaintext" id="nombre" style="font-weight: normal">{{ datos.nombre }}</label>
                            <a v-if="canEdit" class="btn btn-link" @click.prevent="showEditNombre = datos.nombre">
                                <i class="fa fa-pen" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="direccion" class="form-label text-darkgray">DIRECCIÓN</label>
                        <div class="input-group">
                            <label class="form-control-plaintext" id="direccion" style="font-weight: normal">{{ datos.direccion }}</label>
                            <a v-if="canEdit" class="btn btn-link" @click.prevent="showEditDireccion = datos.direccion">
                                <i class="fa fa-pen" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="correo" class="form-label text-darkgray">CORREO ELECTRÓNICO</label>
                        <div class="input-group">
                            <label class="form-control-plaintext" id="correo" style="font-weight: normal">{{ datos.correo }}</label>
                            <a v-if="canEdit" class="btn btn-link" @click.prevent="showEditCorreo = datos.correo">
                                <i class="fa fa-pen" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="telefono" class="form-label text-darkgray">TELÉFONO</label>
                        <div class="input-group">
                            <label class="form-control-plaintext" id="telefono" style="font-weight: normal">{{ datos.telefono }}</label>
                            <a v-if="canEdit" class="btn btn-link" @click.prevent="showEditTelefono = datos.telefono">
                                <i class="fa fa-pen" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showEditNombre" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar nombre de la empresa</h5>
                        <button type="button" class="btn-close" @click="showEditNombre = null"></button>
                    </div>
                    <div class="modal-body">
                        <label for="nombreModal" class="form-label">Nombre de la empresa</label>
                        <input id="nombreModal" class="form-control" v-model="nombreEmpresa" placeholder="Nombre" />
                    </div>
                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEditNombre = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updateNombre">Guardar cambios</button>
                    </div>
                </div>
            </div>

            <div v-if="showEditDireccion" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar dirección</h5>
                        <button type="button" class="btn-close" @click="showEditDireccion = null"></button>
                    </div>
                    <div class="modal-body">
                        <label for="direccionModal" class="form-label">Dirección</label>
                        <input id="direccionModal" class="form-control" v-model="direccionEmpresa" placeholder="Dirección" />
                    </div>
                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEditDireccion = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updateDireccion">Guardar cambios</button>
                    </div>
                </div>
            </div>

            <div v-if="showEditCorreo" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar correo</h5>
                        <button type="button" class="btn-close" @click="showEditCorreo = null"></button>
                    </div>
                    <div class="modal-body">
                        <label for="correoModal" class="form-label">Correo</label>
                        <input id="correoModal" class="form-control" v-model="correoEmpresa" placeholder="Correo" />
                    </div>
                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEditCorreo = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updateCorreo">Guardar cambios</button>
                    </div>
                </div>
            </div>

            <div v-if="showEditTelefono" class="modal-mask">
                <div class="modal-container">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title">Editar teléfono</h5>
                        <button type="button" class="btn-close" @click="showEditTelefono = null"></button>
                    </div>
                    <div class="modal-body">
                        <label for="telefonoModal" class="form-label">Teléfono</label>
                        <input id="telefonoModal" class="form-control" v-model="telefonoEmpresa" placeholder="Teléfono" />
                    </div>
                    <div class="text-end mt-3 modal-footer">
                        <button class="btn btn-secondary" @click="showEditTelefono = null">Cancelar</button>
                        <button class="btn btn-primary" @click="updateTelefono">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </section>
        <NoPermiso v-else mensaje="No tienes permiso para ver las configuraciones de la empresa." />
    </AppLayout>
</template>

<style scoped>
.logo-preview {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 18px;
    border: 1px solid #dee2e6;
    background: #ffffff;
    padding: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

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
