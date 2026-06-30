<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  metodos: {
    type: Array,
    default: () => []
  }
});

const editando = ref(null);

const form = useForm({
  codigo: '',
  nombre: '',
  es_electronico: false,
  permite_pago_unico: true,
  permite_plan_pagos: true,
  descripcion: ''
});

const limpiar = () => {
  editando.value = null;
  form.reset();
  form.es_electronico = false;
  form.permite_pago_unico = true;
  form.permite_plan_pagos = true;
  form.clearErrors();
};

const guardar = () => {
  if (editando.value) {
    form.put(route('metodos-pago.update', editando.value.id), {
      preserveScroll: true,
      onSuccess: limpiar
    });
    return;
  }

  form.post(route('metodos-pago.store'), {
    preserveScroll: true,
    onSuccess: limpiar
  });
};

const editar = (metodo) => {
  editando.value = metodo;
  form.codigo = metodo.codigo;
  form.nombre = metodo.nombre;
  form.es_electronico = Boolean(metodo.es_electronico);
  form.permite_pago_unico = Boolean(metodo.permite_pago_unico);
  form.permite_plan_pagos = Boolean(metodo.permite_plan_pagos);
  form.descripcion = metodo.descripcion || '';
};

const eliminar = (metodo) => {
  if (!confirm(`¿Desea desactivar el método de pago ${metodo.nombre}?`)) return;
  form.delete(route('metodos-pago.destroy', metodo.id), { preserveScroll: true });
};
</script>

<template>
  <AppLayout>
    <div class="row">
      <div class="col-md-4">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">{{ editando ? 'Editar método de pago' : 'Registrar método de pago' }}</h3>
          </div>
          <form @submit.prevent="guardar">
            <div class="card-body">
              <div class="form-group">
                <label>Código</label>
                <input v-model="form.codigo" type="text" class="form-control" placeholder="ej: qr, pagofacil" maxlength="50">
                <small v-if="form.errors.codigo" class="text-danger">{{ form.errors.codigo }}</small>
              </div>

              <div class="form-group">
                <label>Nombre</label>
                <input v-model="form.nombre" type="text" class="form-control" placeholder="Nombre visible" maxlength="120">
                <small v-if="form.errors.nombre" class="text-danger">{{ form.errors.nombre }}</small>
              </div>

              <div class="form-check">
                <input id="electronico" v-model="form.es_electronico" type="checkbox" class="form-check-input">
                <label for="electronico" class="form-check-label">Es pago electrónico</label>
              </div>
              <div class="form-check">
                <input id="pago_unico" v-model="form.permite_pago_unico" type="checkbox" class="form-check-input">
                <label for="pago_unico" class="form-check-label">Permite pago único</label>
              </div>
              <div class="form-check mb-3">
                <input id="plan_pagos" v-model="form.permite_plan_pagos" type="checkbox" class="form-check-input">
                <label for="plan_pagos" class="form-check-label">Permite plan de pagos</label>
              </div>

              <div class="form-group">
                <label>Descripción</label>
                <textarea v-model="form.descripcion" class="form-control" rows="3" maxlength="500"></textarea>
                <small v-if="form.errors.descripcion" class="text-danger">{{ form.errors.descripcion }}</small>
              </div>
            </div>
            <div class="card-footer d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ editando ? 'Actualizar' : 'Guardar' }}
              </button>
              <button type="button" class="btn btn-secondary ml-2" @click="limpiar">Limpiar</button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Métodos de pago registrados</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Electrónico</th>
                  <th>Pago único</th>
                  <th>Plan de pagos</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="metodo in props.metodos" :key="metodo.id">
                  <td>{{ metodo.codigo }}</td>
                  <td>{{ metodo.nombre }}</td>
                  <td>{{ metodo.es_electronico ? 'Sí' : 'No' }}</td>
                  <td>{{ metodo.permite_pago_unico ? 'Sí' : 'No' }}</td>
                  <td>{{ metodo.permite_plan_pagos ? 'Sí' : 'No' }}</td>
                  <td>
                    <button class="btn btn-sm btn-warning mr-1" @click="editar(metodo)">Editar</button>
                    <button class="btn btn-sm btn-danger" @click="eliminar(metodo)">Desactivar</button>
                  </td>
                </tr>
                <tr v-if="props.metodos.length === 0">
                  <td colspan="6" class="text-center text-muted">No hay métodos de pago registrados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
