<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NoPermiso from '@/Components/NoPermiso.vue';

const props = defineProps({
  pagos: { type: Array, default: () => [] },
  proveedores: { type: Array, default: () => [] },
  comprasPendientes: { type: Array, default: () => [] },
});
const page = usePage();
const errors = computed(() => page.props.errors || {});
const can = (funcionalidad) => page.props.auth?.privilegios?.[funcionalidad]?.leer;
const canAdd = computed(() => page.props.auth?.privilegios?.PagoProveedor?.agregar);
const canDelete = computed(() => page.props.auth?.privilegios?.PagoProveedor?.borrar);
const showAdd = ref(false);
const showDelete = ref(null);
const today = new Date().toISOString().slice(0, 10);
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;

const form = ref({ id_proveedor: '', id_compra: '', monto: 0, fecha_pago: today, metodo_pago: 'efectivo', referencia: '', observaciones: '' });
const comprasFiltradas = computed(() => props.comprasPendientes.filter(c => !form.value.id_proveedor || Number(c.id_proveedor) === Number(form.value.id_proveedor)));
const compraSeleccionada = computed(() => props.comprasPendientes.find(c => Number(c.id) === Number(form.value.id_compra)) || null);
watch(() => form.value.id_compra, () => { if (compraSeleccionada.value) { form.value.id_proveedor = compraSeleccionada.value.id_proveedor; form.value.monto = Number(compraSeleccionada.value.saldo || 0); } });
function resetForm() { form.value = { id_proveedor: '', id_compra: '', monto: 0, fecha_pago: today, metodo_pago: 'efectivo', referencia: '', observaciones: '' }; }
function storePago() { router.post(route('pagos-proveedor.store'), form.value, { preserveScroll: true, onSuccess: () => { showAdd.value = false; resetForm(); } }); }
function deletePago(id) { router.delete(route('pagos-proveedor.destroy', id), { preserveScroll: true, onSuccess: () => { showDelete.value = null; } }); }
</script>

<template>
  <AppLayout title="Pagos a proveedores">
    <section class="content" v-if="can('PagoProveedor')">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h1 class="card-title mb-0"><i class="fas fa-money-check-alt mr-2"></i><b>PAGOS A PROVEEDORES</b></h1>
        <button v-if="canAdd" class="btn btn-success" @click="showAdd = true"><i class="fa fa-plus"></i>&nbsp; Registrar pago</button>
      </div>

      <div class="row mt-3">
        <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h4>{{ pagos.length }}</h4><p>Pagos registrados</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h4>{{ money(pagos.reduce((a,p)=>a+Number(p.monto||0),0)) }}</h4><p>Total pagado</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-warning"><div class="inner"><h4>{{ comprasPendientes.length }}</h4><p>Compras con saldo</p></div></div></div>
      </div>

      <div class="card table-responsive">
        <div class="card-body">
          <table class="table table-hover table-sm align-middle">
            <thead class="table-light"><tr><th>ID</th><th>FECHA</th><th>PROVEEDOR</th><th>COMPRA</th><th>MONTO</th><th>MÉTODO</th><th>REFERENCIA</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
            <tbody>
              <tr v-for="pago in pagos" :key="pago.id">
                <td>{{ pago.id }}</td><td>{{ pago.fecha_pago }}</td><td>{{ pago.proveedor?.nombre || '-' }}</td><td>{{ pago.compra ? 'Compra Nº ' + pago.compra.id : 'Pago general' }}</td><td>{{ money(pago.monto) }}</td><td>{{ pago.metodo_pago }}</td><td>{{ pago.referencia || '-' }}</td><td><span class="badge" :class="pago.estado === 'confirmado' ? 'badge-success' : 'badge-danger'">{{ pago.estado }}</span></td>
                <td><a v-if="canDelete && pago.estado === 'confirmado'" href="#" @click.prevent="showDelete = pago"><i class="fa fa-trash"></i></a></td>
              </tr>
              <tr v-if="pagos.length === 0"><td colspan="9" class="text-center text-muted">No hay pagos registrados.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="showAdd" class="modal-mask">
        <div class="modal-container modal-lg-custom">
          <div class="modal-header"><h5>Registrar pago a proveedor</h5><button class="btn-close" @click="showAdd = false"></button></div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-2"><label>Proveedor *</label><select class="form-control" v-model="form.id_proveedor"><option value="">Seleccione</option><option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.nombre }}</option></select><p v-if="errors.id_proveedor" class="text-danger">{{ errors.id_proveedor }}</p></div>
              <div class="col-md-6 mb-2"><label>Compra pendiente</label><select class="form-control" v-model="form.id_compra"><option value="">Pago general / sin compra</option><option v-for="c in comprasFiltradas" :key="c.id" :value="c.id">Compra Nº {{ c.id }} - {{ c.proveedor?.nombre }} - saldo {{ money(c.saldo) }}</option></select><p v-if="errors.id_compra" class="text-danger">{{ errors.id_compra }}</p></div>
              <div class="col-md-4 mb-2"><label>Monto *</label><input type="number" min="0.01" step="0.01" class="form-control" v-model.number="form.monto"><p v-if="errors.monto" class="text-danger">{{ errors.monto }}</p></div>
              <div class="col-md-4 mb-2"><label>Fecha</label><input type="date" class="form-control" v-model="form.fecha_pago"></div>
              <div class="col-md-4 mb-2"><label>Método</label><select class="form-control" v-model="form.metodo_pago"><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="qr">QR</option><option value="pagofacil">PagoFácil</option><option value="tarjeta">Tarjeta</option><option value="cheque">Cheque</option><option value="otro">Otro</option></select></div>
              <div class="col-md-6 mb-2"><label>Referencia</label><input class="form-control" v-model="form.referencia"></div>
              <div class="col-md-6 mb-2"><label>Observaciones</label><input class="form-control" v-model="form.observaciones"></div>
            </div>
            <div v-if="compraSeleccionada" class="alert alert-info mt-2 mb-0">Saldo de la compra seleccionada: <b>{{ money(compraSeleccionada.saldo) }}</b></div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" @click="showAdd = false">Cancelar</button><button class="btn btn-primary" @click="storePago">Registrar pago</button></div>
        </div>
      </div>

      <div v-if="showDelete" class="modal-mask">
        <div class="modal-container">
          <div class="modal-header"><h5>Anular pago</h5><button class="btn-close" @click="showDelete = null"></button></div>
          <div class="modal-body">¿Anular el pago Nº <b>{{ showDelete.id }}</b> por {{ money(showDelete.monto) }}?</div>
          <div class="modal-footer"><button class="btn btn-secondary" @click="showDelete = null">Cancelar</button><button class="btn btn-danger" @click="deletePago(showDelete.id)">Anular</button></div>
        </div>
      </div>
    </section>
    <NoPermiso v-else mensaje="No tienes permiso para ver pagos a proveedores." />
  </AppLayout>
</template>

<style scoped>
.modal-mask{position:fixed;z-index:9999;background:rgba(0,0,0,.35);top:0;left:0;right:0;bottom:0;align-items:flex-start;justify-content:center;display:flex;width:100vw;overflow-y:auto;padding:20px}.modal-container{background:#fff;width:min(560px,94%);border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.33)}.modal-lg-custom{width:min(900px,96%)}
</style>
