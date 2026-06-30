<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    roles: Array,
});

const emit = defineEmits(["close"]);

const form = useForm({
    nombre: "",
    telefono: "",
    email: "",
    password: "",
    id_rol: "",
});

const submit = () => {
    form.post(route("usuario.store"), {
        onSuccess: () => {
            form.reset();
            emit("close");
        },
    });
};
</script>

<template>
    <div v-if="show" class="modal-mask">
        <div class="modal-container">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title">Agregar Usuario</h5>
                <button type="button" class="btn-close" @click="$emit('close')"></button>
            </div>

            <form @submit.prevent="submit">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" v-model="form.nombre" placeholder="Nombre" />
                        <div v-if="form.errors.nombre" class="text-danger">{{ form.errors.nombre }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input class="form-control" v-model="form.telefono" placeholder="Teléfono" />
                        <div v-if="form.errors.telefono" class="text-danger">{{ form.errors.telefono }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" v-model="form.email" placeholder="Email" />
                        <div v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" v-model="form.password" placeholder="Contraseña" />
                        <div v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" v-model="form.id_rol">
                            <option value="" disabled>Seleccione un rol</option>
                            <option v-for="rol in roles" :key="rol.id" :value="rol.id">
                                {{ rol.nombre }}
                            </option>
                        </select>
                        <div v-if="form.errors.id_rol" class="text-danger">{{ form.errors.id_rol }}</div>
                    </div>
                </div>

                <div class="text-end mt-3 modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar</button>
                </div>
            </form>
        </div>
    </div>
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
    width: min(500px, 90%);
    border-radius: 8px;
    padding: 20px;
}
</style>