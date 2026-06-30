<script setup>
import { Link } from '@inertiajs/vue3';
const empacarLogo = '/img/empacar-brand.svg';
</script>

<template>
  <div class="empacar-landing min-vh-100">
    <header class="landing-navbar">
      <div class="container d-flex align-items-center justify-content-between py-3">
        <div class="brand-wrap d-flex align-items-center gap-2">
          <img :src="empacarLogo" alt="EMPACAR S.A." class="landing-logo">
          <div>
            <strong>EMPACAR S.A.</strong>
            <small class="d-block">Sistema comercial administrativo</small>
          </div>
        </div>
        <Link :href="route('login')" class="btn btn-empacar-outline">Ingresar</Link>
      </div>
    </header>

    <main class="landing-hero">
      <div class="container py-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <span class="empacar-kicker">Industria · Reciclaje · Empaques · Sostenibilidad</span>
            <h1 class="display-5 fw-bold mb-3">
              Gestión comercial para EMPACAR S.A. con ventas, inventario, cuotas y pagos electrónicos.
            </h1>
            <p class="lead mb-4">
              Prototipo corporativo para controlar productos, clientes, proveedores, compras, cuentas por cobrar y reportes de negocio desde una sola plataforma.
            </p>
            <div class="d-flex flex-wrap gap-2">
              <Link :href="route('login')" class="btn btn-empacar btn-lg px-4">Ingresar al sistema</Link>
              <a href="#divisiones" class="btn btn-light btn-lg px-4">Ver divisiones</a>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="hero-card">
              <div class="hero-card-header">
                <i class="fas fa-recycle"></i>
                <span>Cadena productiva sostenible</span>
              </div>
              <div class="metric-row">
                <div><strong>PET</strong><small>Preformas y botellas</small></div>
                <div><strong>Kraft</strong><small>Bolsas personalizadas</small></div>
              </div>
              <div class="metric-row">
                <div><strong>Corrugado</strong><small>Cajas y empaques</small></div>
                <div><strong>Reciclado</strong><small>Materias primas</small></div>
              </div>
              <div class="hero-note">
                Sistema adaptado a ventas únicas, planes de pago y operaciones internas de inventario.
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <section id="divisiones" class="container py-5">
      <div class="text-center mb-4">
        <span class="empacar-kicker">Módulos del prototipo</span>
        <h2 class="fw-bold">Operación comercial alineada a EMPACAR</h2>
        <p class="text-muted mb-0">Una interfaz inspirada en sostenibilidad, industria y control de empaques.</p>
      </div>

      <div class="row g-3">
        <div class="col-md-4" v-for="item in [
          ['fa-box-open', 'Productos e inventario', 'Control de divisiones PET, corrugado, kraft, reciclado, inyección, láminas y termoformado.'],
          ['fa-cash-register', 'Ventas y cuotas', 'Ventas al contado, crédito, planes de pago y registro de cuotas por cliente.'],
          ['fa-truck-loading', 'Compras y proveedores', 'Seguimiento de compras, solicitudes internas, proveedores y pagos pendientes.'],
          ['fa-qrcode', 'Pagos electrónicos', 'Registro de métodos de pago, QR, PagoFácil y pagos únicos o parciales.'],
          ['fa-chart-line', 'Reportes', 'Indicadores financieros, productos top, visitas y estadísticas de acceso.'],
          ['fa-shield-alt', 'Matriz de acceso', 'Roles de negocio, menú dinámico, bitácora y control de recursos accedidos.']
        ]" :key="item[1]">
          <div class="landing-module-card h-100">
            <i :class="['fas', item[0]]"></i>
            <h5>{{ item[1] }}</h5>
            <p>{{ item[2] }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.empacar-landing {
  background: linear-gradient(180deg, #F4FAF6 0%, #FFFFFF 52%, #F1F8F4 100%);
  color: #063B2B;
  overflow-x: hidden;
}

.landing-navbar {
  background: rgba(255,255,255,.92);
  border-bottom: 1px solid #DDEFE4;
  backdrop-filter: blur(12px);
  position: sticky;
  top: 0;
  z-index: 20;
}

.landing-logo {
  width: 150px;
  height: 48px;
  object-fit: contain;
}

.brand-wrap strong {
  color: #006B3F;
  letter-spacing: .4px;
}

.brand-wrap small {
  color: #597266;
  font-size: .78rem;
}

.landing-hero {
  background:
    radial-gradient(circle at 15% 10%, rgba(141,198,63,.22), transparent 34%),
    radial-gradient(circle at 92% 22%, rgba(0,107,166,.18), transparent 30%),
    linear-gradient(135deg, #FFFFFF 0%, #F3FAF5 100%);
  border-bottom: 1px solid #DDEFE4;
}

.empacar-kicker {
  display: inline-flex;
  padding: .45rem .8rem;
  border-radius: 999px;
  background: rgba(0,168,89,.12);
  color: #006B3F;
  font-weight: 800;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .06rem;
  margin-bottom: 1rem;
}

.lead {
  color: #435E52;
}

.btn-empacar,
.btn-empacar-outline {
  border-radius: 999px;
  font-weight: 800;
}

.btn-empacar {
  background: linear-gradient(135deg, #006B3F, #00A859);
  border: 0;
  color: #FFFFFF;
  box-shadow: 0 12px 26px rgba(0,107,63,.22);
}

.btn-empacar:hover {
  color: #FFFFFF;
  transform: translateY(-1px);
  filter: brightness(.96);
}

.btn-empacar-outline {
  border: 2px solid #006B3F;
  color: #006B3F;
  background: #FFFFFF;
}

.hero-card,
.landing-module-card {
  background: #FFFFFF;
  border: 1px solid #DDEFE4;
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(6,59,43,.12);
}

.hero-card {
  padding: 1.35rem;
}

.hero-card-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  color: #006B3F;
  font-weight: 900;
  margin-bottom: 1rem;
}

.hero-card-header i {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  background: #E6F6EC;
}

.metric-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
  margin-bottom: .75rem;
}

.metric-row div {
  background: #F3FAF5;
  border-radius: 18px;
  padding: .9rem;
}

.metric-row strong,
.metric-row small {
  display: block;
}

.metric-row strong {
  color: #063B2B;
}

.metric-row small {
  color: #5D756A;
}

.hero-note {
  background: linear-gradient(135deg, #006B3F, #006BA6);
  color: #FFFFFF;
  padding: 1rem;
  border-radius: 18px;
  font-weight: 700;
}

.landing-module-card {
  padding: 1.2rem;
  transition: all .18s ease;
}

.landing-module-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 40px rgba(6,59,43,.16);
}

.landing-module-card i {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  background: linear-gradient(135deg, #006B3F, #00A859);
  color: #FFFFFF;
  margin-bottom: .9rem;
}

.landing-module-card h5 {
  color: #063B2B;
  font-weight: 900;
}

.landing-module-card p {
  color: #5D756A;
  margin-bottom: 0;
}
</style>
