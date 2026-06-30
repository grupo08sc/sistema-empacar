<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ compra: Object });
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;
const badgeClass = (estado) => estado === 'aprobada' ? 'badge badge-success' : estado === 'pendiente' ? 'badge badge-warning' : estado === 'rechazada' ? 'badge badge-danger' : estado === 'anulada' ? 'badge badge-secondary' : 'badge badge-info';
</script>

<template>
  <AppLayout title="Detalle compra">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h3 class="card-title mb-0"><b>Compra / Solicitud Nº {{ compra.id }}</b></h3>
          <small class="text-muted">Flujo de solicitud, aprobación administrativa y ejecución de inventario.</small>
        </div>
        <Link :href="route('compras.index')" class="btn btn-secondary btn-sm">Volver</Link>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <p><b>Proveedor:</b> {{ compra.proveedor?.nombre }}</p>
            <p><b>Fecha requerida/compra:</b> {{ compra.fecha_compra }}</p>
            <p><b>Solicitado por:</b> {{ compra.solicitante?.nombre || compra.usuario?.nombre || compra.usuario?.email }}</p>
          </div>
          <div class="col-md-4">
            <p><b>Estado de aprobación:</b> <span :class="badgeClass(compra.estado_aprobacion)">{{ compra.estado_aprobacion }}</span></p>
            <p><b>Estado de pago:</b> {{ compra.estado }}</p>
            <p><b>Stock aplicado:</b> {{ compra.stock_aplicado ? 'Sí' : 'No' }}</p>
          </div>
          <div class="col-md-4">
            <p><b>Total:</b> {{ money(compra.total) }}</p>
            <p><b>Pago inicial/proveedor:</b> {{ money(compra.monto_pagado) }}</p>
            <p><b>Saldo:</b> {{ money(compra.saldo) }}</p>
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-md-6">
            <div class="border rounded p-2 h-100">
              <b>Observación de solicitud</b>
              <p class="mb-0 text-muted">{{ compra.observaciones || 'Sin observaciones.' }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-2 h-100">
              <b>Decisión administrativa</b>
              <p class="mb-1"><b>Aprobado/Revisado por:</b> {{ compra.aprobador?.nombre || '-' }}</p>
              <p class="mb-1"><b>Fecha decisión:</b> {{ compra.fecha_aprobacion || '-' }}</p>
              <p v-if="compra.observacion_aprobacion" class="mb-1"><b>Observación:</b> {{ compra.observacion_aprobacion }}</p>
              <p v-if="compra.motivo_rechazo" class="mb-0 text-danger"><b>Motivo rechazo:</b> {{ compra.motivo_rechazo }}</p>
            </div>
          </div>
        </div>

        <hr>
        <h5>Detalle de productos</h5>
        <table class="table table-sm table-hover">
          <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio compra</th><th>Subtotal</th></tr></thead>
          <tbody>
            <tr v-for="detalle in compra.detalles" :key="detalle.id"><td>{{ detalle.producto?.nombre }}</td><td>{{ detalle.cantidad }}</td><td>{{ money(detalle.precio_unitario) }}</td><td>{{ money(detalle.subtotal) }}</td></tr>
          </tbody>
        </table>

        <h5>Pagos al proveedor</h5>
        <table class="table table-sm table-hover">
          <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Estado</th></tr></thead>
          <tbody>
            <tr v-for="pago in compra.pagos" :key="pago.id"><td>{{ pago.fecha_pago }}</td><td>{{ money(pago.monto) }}</td><td>{{ pago.metodo_pago }}</td><td>{{ pago.referencia || '-' }}</td><td>{{ pago.estado }}</td></tr>
            <tr v-if="!compra.pagos?.length"><td colspan="5" class="text-center text-muted">Sin pagos registrados.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
