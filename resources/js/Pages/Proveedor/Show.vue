<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ proveedor: Object });
const money = (v) => `Bs ${Number(v || 0).toFixed(2)}`;
</script>

<template>
  <AppLayout title="Detalle proveedor">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><b>{{ proveedor.nombre }}</b></h3>
        <Link :href="route('proveedores.index')" class="btn btn-secondary btn-sm">Volver</Link>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p><b>NIT:</b> {{ proveedor.nit || '-' }}</p>
            <p><b>Teléfono:</b> {{ proveedor.telefono || '-' }}</p>
            <p><b>Email:</b> {{ proveedor.email || '-' }}</p>
          </div>
          <div class="col-md-6">
            <p><b>Contacto:</b> {{ proveedor.contacto || '-' }}</p>
            <p><b>Dirección:</b> {{ proveedor.direccion || '-' }}</p>
            <p><b>Estado:</b> {{ proveedor.estado }}</p>
          </div>
        </div>
        <hr>
        <h5>Compras</h5>
        <table class="table table-sm table-hover">
          <thead><tr><th>ID</th><th>Fecha</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Estado</th></tr></thead>
          <tbody>
            <tr v-for="compra in proveedor.compras" :key="compra.id"><td>{{ compra.id }}</td><td>{{ compra.fecha_compra }}</td><td>{{ money(compra.total) }}</td><td>{{ money(compra.monto_pagado) }}</td><td>{{ money(compra.saldo) }}</td><td>{{ compra.estado }}</td></tr>
            <tr v-if="!proveedor.compras?.length"><td colspan="6" class="text-center text-muted">Sin compras registradas.</td></tr>
          </tbody>
        </table>
        <h5>Pagos</h5>
        <table class="table table-sm table-hover">
          <thead><tr><th>ID</th><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th></tr></thead>
          <tbody>
            <tr v-for="pago in proveedor.pagos" :key="pago.id"><td>{{ pago.id }}</td><td>{{ pago.fecha_pago }}</td><td>{{ money(pago.monto) }}</td><td>{{ pago.metodo_pago }}</td><td>{{ pago.referencia || '-' }}</td></tr>
            <tr v-if="!proveedor.pagos?.length"><td colspan="5" class="text-center text-muted">Sin pagos registrados.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
