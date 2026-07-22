<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDateFormatter } from "../../Composables/useDateFormatter";

const { formatDate, formatDateTime } = useDateFormatter();
const props = defineProps({ solicitud: Object });
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;
</script>

<template>
  <AppLayout title="Detalle solicitud">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><b>Solicitud Nº {{ solicitud.id }}</b></h3>
        <Link :href="route('solicitudes.index')" class="btn btn-secondary btn-sm">Volver</Link>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4"><p><b>Solicitante:</b> {{ solicitud.usuario?.nombre || solicitud.usuario?.email }}</p><p><b>Departamento:</b> {{ solicitud.departamento?.nombre || '-' }}</p></div>
          <div class="col-md-4"><p><b>Fecha solicitud:</b> {{ formatDate(solicitud.fecha_solicitud) }}</p><p><b>Fecha requerida:</b> {{ formatDate(solicitud.fecha_requerida) || '-' }}</p></div>
          <div class="col-md-4"><p><b>Estado:</b> {{ solicitud.estado }}</p><p><b>Moneda:</b> {{ solicitud.moneda }}</p></div>
        </div>
        <p><b>Descripción:</b> {{ solicitud.descripcion }}</p>
        <p><b>Justificación:</b> {{ solicitud.justificacion || '-' }}</p>
        <hr>
        <h5>Detalle solicitado</h5>
        <table class="table table-sm table-hover">
          <thead><tr><th>Producto</th><th>Artículo</th><th>Cantidad</th><th>Precio estimado</th><th>Importe</th></tr></thead>
          <tbody>
            <tr v-for="detalle in solicitud.detalles" :key="detalle.id"><td>{{ detalle.producto?.nombre || '-' }}</td><td>{{ detalle.nombre_articulo || '-' }}</td><td>{{ detalle.cantidad }}</td><td>{{ money(detalle.precio_estimado) }}</td><td>{{ money(detalle.importe) }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
