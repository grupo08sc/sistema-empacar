<script setup>
import { computed, ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NoPermiso from '@/Components/NoPermiso.vue';

const props = defineProps({ proveedores: { type: Array, default: () => [] } });
const page = usePage();
const errors = computed(() => page.props.errors || {});
const can = (funcionalidad) => page.props.auth?.privilegios?.[funcionalidad]?.leer;
const canAdd = computed(() => page.props.auth?.privilegios?.Proveedor?.agregar);
const canEdit = computed(() => page.props.auth?.privilegios?.Proveedor?.modificar);
const canDelete = computed(() => page.props.auth?.privilegios?.Proveedor?.borrar);

const showAdd = ref(false);
const showEdit = ref(null);
const showDelete = ref(null);
const form = ref({ nombre: '', nit: '', telefono: '', email: '', direccion: '', contacto: '', estado: 'activo' });
const editForm = ref({ id: null, nombre: '', nit: '', telefono: '', email: '', direccion: '', contacto: '', estado: 'activo' });

function resetForm() {
  form.value = { nombre: '', nit: '', telefono: '', email: '', direccion: '', contacto: '', estado: 'activo' };
}
function openEdit(proveedor) {
  showEdit.value = proveedor.id;
  editForm.value = { ...proveedor };
}
function storeProveedor() {
  router.post(route('proveedores.store'), form.value, {
    preserveScroll: true,
    onSuccess: () => { showAdd.value = false; resetForm(); }
  });
}
function updateProveedor() {
  router.put(route('proveedores.update', editForm.value.id), editForm.value, {
    preserveScroll: true,
    onSuccess: () => { showEdit.value = null; }
  });
}
function deleteProveedor(id) {
  router.delete(route('proveedores.destroy', id), {
    preserveScroll: true,
    onSuccess: () => { showDelete.value = null; }
  });
}
function badgeClass(estado) {
  return estado === 'activo' ? 'badge badge-success' : estado === 'bloqueado' ? 'badge badge-danger' : 'badge badge-secondary';
}
</script>

<template>
  <AppLayout title="Proveedores">
    <section class="content" v-if="can('Proveedor')">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h1 class="card-title mb-0"><i class="fas fa-truck mr-2"></i><b>GESTIONAR PROVEEDORES</b></h1>
        <button v-if="canAdd" class="btn btn-success" @click="showAdd = true"><i class="fa fa-plus"></i>&nbsp; Agregar</button>
      </div>

      <div class="card table-responsive mt-3">
        <div class="card-body">
          <table class="table table-hover table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th><th>NOMBRE</th><th>NIT</th><th>TELÉFONO</th><th>EMAIL</th><th>CONTACTO</th><th>ESTADO</th><th>COMPRAS</th><th>PAGOS</th><th>ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="proveedor in proveedores" :key="proveedor.id">
                <td>{{ proveedor.id }}</td>
                <td><b>{{ proveedor.nombre }}</b><br><small>{{ proveedor.direccion || 'Sin dirección' }}</small></td>
                <td>{{ proveedor.nit || '-' }}</td>
                <td>{{ proveedor.telefono || '-' }}</td>
                <td>{{ proveedor.email || '-' }}</td>
                <td>{{ proveedor.contacto || '-' }}</td>
                <td><span :class="badgeClass(proveedor.estado)">{{ proveedor.estado }}</span></td>
                <td>{{ proveedor.compras_count ?? 0 }}</td>
                <td>{{ proveedor.pagos_count ?? 0 }}</td>
                <td class="text-nowrap">
                  <Link :href="route('proveedores.show', proveedor.id)" class="mr-2"><i class="fa fa-eye"></i></Link>
                  <a v-if="canEdit" href="#" class="mr-2" @click.prevent="openEdit(proveedor)"><i class="fa fa-edit"></i></a>
                  <a v-if="canDelete" href="#" @click.prevent="showDelete = proveedor"><i class="fa fa-trash"></i></a>
                </td>
              </tr>
              <tr v-if="proveedores.length === 0"><td colspan="10" class="text-center text-muted">No hay proveedores registrados.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="showAdd" class="modal-mask">
        <div class="modal-container modal-lg-custom">
          <div class="modal-header"><h5 class="modal-title">Agregar proveedor</h5><button class="btn-close" @click="showAdd = false"></button></div>
          <div class="modal-body grid-form">
            <div><label>Nombre *</label><input class="form-control" v-model="form.nombre"><p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p></div>
            <div><label>NIT</label><input class="form-control" v-model="form.nit"></div>
            <div><label>Teléfono</label><input class="form-control" v-model="form.telefono"></div>
            <div><label>Email</label><input class="form-control" v-model="form.email"><p v-if="errors.email" class="text-danger">{{ errors.email }}</p></div>
            <div><label>Contacto</label><input class="form-control" v-model="form.contacto"></div>
            <div><label>Estado</label><select class="form-control" v-model="form.estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="bloqueado">Bloqueado</option></select></div>
            <div class="grid-span"><label>Dirección</label><input class="form-control" v-model="form.direccion"></div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" @click="showAdd = false">Cancelar</button><button class="btn btn-primary" @click="storeProveedor">Guardar</button></div>
        </div>
      </div>

      <div v-if="showEdit" class="modal-mask">
        <div class="modal-container modal-lg-custom">
          <div class="modal-header"><h5 class="modal-title">Editar proveedor</h5><button class="btn-close" @click="showEdit = null"></button></div>
          <div class="modal-body grid-form">
            <div><label>Nombre *</label><input class="form-control" v-model="editForm.nombre"><p v-if="errors.nombre" class="text-danger">{{ errors.nombre }}</p></div>
            <div><label>NIT</label><input class="form-control" v-model="editForm.nit"></div>
            <div><label>Teléfono</label><input class="form-control" v-model="editForm.telefono"></div>
            <div><label>Email</label><input class="form-control" v-model="editForm.email"><p v-if="errors.email" class="text-danger">{{ errors.email }}</p></div>
            <div><label>Contacto</label><input class="form-control" v-model="editForm.contacto"></div>
            <div><label>Estado</label><select class="form-control" v-model="editForm.estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="bloqueado">Bloqueado</option></select></div>
            <div class="grid-span"><label>Dirección</label><input class="form-control" v-model="editForm.direccion"></div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" @click="showEdit = null">Cancelar</button><button class="btn btn-primary" @click="updateProveedor">Guardar cambios</button></div>
        </div>
      </div>

      <div v-if="showDelete" class="modal-mask">
        <div class="modal-container">
          <div class="modal-header"><h5 class="modal-title">Desactivar proveedor</h5><button class="btn-close" @click="showDelete = null"></button></div>
          <div class="modal-body">¿Deseas desactivar a <b>{{ showDelete.nombre }}</b>?</div>
          <div class="modal-footer"><button class="btn btn-secondary" @click="showDelete = null">Cancelar</button><button class="btn btn-danger" @click="deleteProveedor(showDelete.id)">Desactivar</button></div>
        </div>
      </div>
    </section>
    <NoPermiso v-else mensaje="No tienes permiso para ver proveedores." />
  </AppLayout>
</template>

<style scoped>
.modal-mask{position:fixed;z-index:9999;background:rgba(0,0,0,.35);top:0;left:0;right:0;bottom:0;align-items:center;justify-content:center;display:flex;width:100vw;overflow-y:auto;padding:20px}.modal-container{background:#fff;width:min(540px,94%);border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.33)}.modal-lg-custom{width:min(900px,96%)}.grid-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.grid-span{grid-column:1/-1}@media(max-width:768px){.grid-form{grid-template-columns:1fr}}
</style>
