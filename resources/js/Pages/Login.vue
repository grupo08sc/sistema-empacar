<script setup>
const empacarLogo = '/img/empacar-brand.svg';
</script>

<template>
  <div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #F3FAF5, #FFFFFF);">
    <div class="card shadow border-0" style="max-width: 440px; width: 100%; border-radius: 24px;">
      <div class="card-body p-4 text-center">
        <img :src="empacarLogo" alt="EMPACAR S.A." style="width: 190px;" class="mb-3">
        <h3 class="fw-bold mb-2" style="color:#006B3F;">EMPACAR S.A.</h3>
        <p class="text-muted mb-4">Sistema comercial para ventas, compras, cuotas, pagos e inventario.</p>
        <a href="/login" class="btn btn-primary w-100" style="border-radius:999px;background:#006B3F;border-color:#006B3F;">Continuar al inicio de sesión</a>
      </div>
    </div>
  </div>
</template>
