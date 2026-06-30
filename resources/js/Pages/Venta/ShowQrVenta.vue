<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';


const props = defineProps({
    venta: Object,
    qrImage: String,
    callbackUrl: String,
    montoRealSistema: [Number, String],
    montoQrPrueba: [Number, String]
})
const ventaActual = ref(props.venta);
const estadoTransaccion = ref('generado');
const estadoPagoFacil = ref(null);
const mensajeEstado = ref('Esperando confirmación de PagoFácil.');

let pollInterval = null;
let countdownInterval = null;
const isPolling = ref(false);
const isCountdown = ref(false);

const startPolling = () => {
    if (ventaActual.value.estado === 'pagado') return;

    isPolling.value = true;
    console.log('Iniciando consulta de estado PagoFácil...');

    pollInterval = setInterval(async () => {
        if (ventaActual.value.estado === 'pagado') {
            stopPolling();
            return;
        }

        try {
            const response = await fetch(route('pagofacil.estado.venta', ventaActual.value.id), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();

            if (data.venta) {
                ventaActual.value = data.venta;
            }

            estadoPagoFacil.value = data.estado_pagofacil ?? estadoPagoFacil.value;
            estadoTransaccion.value = data.estado_transaccion || data.estado_normalizado || estadoTransaccion.value;
            mensajeEstado.value = data.message || mensajeEstado.value;

            console.log('Estado PagoFácil venta:', data.estado_pagofacil, data.estado_transaccion);

            if (data.pagado || ventaActual.value.estado === 'pagado') {
                console.log('Pago detectado por consulta de estado PagoFácil.');
                stopPolling();
                stopCountdown();
            }

            if (['anulado', 'expirado', 'cancelado'].includes(estadoTransaccion.value)) {
                stopPolling();
                stopCountdown();
            }
        } catch (error) {
            console.error('Error al consultar estado PagoFácil:', error);
        }
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    isPolling.value = false;
};

const tiempoRestante = ref(300);

const formatoTiempo = (segundos) => {
    const minutos = Math.floor(segundos / 60);
    const segundosRestantes = segundos % 60;
    return `${minutos}:${segundosRestantes < 10 ? '0' : ''}${segundosRestantes}`;
};

const actualizarTiempoRestante = () => {
    tiempoRestante.value--;
    if (tiempoRestante.value <= 0) {
        stopPolling();
        stopCountdown();
    }
};

const startCountdown = () => {
    tiempoRestante.value = 300;
    countdownInterval = setInterval(actualizarTiempoRestante, 1000);
    isCountdown.value = true;
};

const stopCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    isCountdown.value = false;
};

onMounted(() => {
    startPolling();
    startCountdown();
});

onUnmounted(() => {
    stopPolling();
    stopCountdown();
});

// Helper de formato
const formatoMoneda = (valor) => {
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(valor);
};
</script>

<template>
    <AppLayout>
        <div class="d-flex flex-column justify-content-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-6 col-lg-5">

                        <!-- HEADER -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-dark">Pago con QR</h2>
                            <p class="text-muted small">
                                {{ ventaActual.planes || 'Venta' }}
                            </p>
                            <p>
                                {{ route('pagofacil.callback') }}
                            </p>
                        </div>

                        <!-- CARD PRINCIPAL -->
                        <div class="card shadow border-0 rounded-3">
                            <div class="card-body p-4 text-center">

                                <!-- ESTADO: PAGADO -->
                                <div v-if="ventaActual.estado === 'pagado'" class="fade-in">
                                    <div class="mb-4">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-25 rounded-circle"
                                            style="width: 64px; height: 64px;">
                                            <i class="fa fa-check fa-2x text-white animate-fade-in-up"></i>
                                        </div>
                                    </div>

                                    <h3 class="h4 fw-bold text-dark mb-2">¡Pago Realizado!</h3>
                                    <p class="text-muted mb-4">
                                        Su venta #{{ ventaActual.id }} ha sido cancelada correctamente.
                                    </p>

                                    <div class="bg-light p-3 rounded mb-4 text-start border">
                                        <div class="d-flex justify-content-between text-muted small mb-2">
                                            <span class="fw-bold">Transacción:</span>
                                            <span class="text-dark font-monospace">{{
                                                ventaActual.pagofacil_transaction_id }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span class="fw-bold">Fecha:</span>
                                            <span class="text-dark">{{ new Date().toLocaleDateString() }}</span>
                                        </div>
                                    </div>

                                    <Link v-if="ventaActual" :href="route('venta.index', ventaActual.id)"
                                        class="btn btn-primary w-100 py-2 fw-bold">
                                    Volver al Plan de Pagos
                                    </Link>
                                </div>

                                <!-- ESTADO: PENDIENTE -->
                                <div v-else>
                                    <div class="mb-4 d-flex justify-content-center gap-2 flex-wrap">
                                        <span
                                            class="badge rounded-pill bg-primary bg-opacity-90 text-white border border-primary border-opacity-25 px-3 py-2">
                                            Venta {{ ventaActual.id }}
                                        </span>
                                        <span
                                            class="badge rounded-pill bg-info bg-opacity-10 text-white border border-info border-opacity-25 px-3 py-2">
                                            ID: {{ ventaActual.pagofacil_transaction_id }}
                                        </span>
                                    </div>

                                    <p class="text-muted small mb-3">
                                        Escanea el siguiente código QR desde tu aplicación bancaria móvil.
                                    </p>

                                    <div class="alert alert-warning text-start small mb-4">
                                        <strong>PagoFácil en modo prototipo:</strong>
                                        el QR se genera por {{ formatoMoneda(props.montoQrPrueba || 0.01) }} para pruebas.
                                        Al confirmarse el pago, el sistema registra internamente el monto real de
                                        {{ formatoMoneda(props.montoRealSistema || 0) }}.
                                    </div>

                                    <!-- Contenedor QR -->
                                    <div class="d-flex justify-content-center mb-4 position-relative">
                                        <!-- Si hay imagen QR -->
                                        <div v-if="qrImage" class=" border rounded bg-white shadow-sm">
                                            <img :src="'data:image/png;base64,' + qrImage" alt="QR PagoFácil"
                                                class="img-fluid" style="width: auto; height: auto;" />
                                        </div>

                                        <!-- Estado de Carga (Skeleton) -->
                                        <div v-else
                                            class="bg-light border rounded d-flex flex-col align-items-center justify-content-center"
                                            style="width: 300px; height: 300px;">
                                            <div class="spinner-border text-secondary mb-2" role="status"></div>
                                            <span class="small text-muted">Generando QR...</span>
                                        </div>
                                    </div>

                                    <!-- Indicador de estado PagoFácil -->
                                    <div v-if="estadoTransaccion === 'anulado' || estadoTransaccion === 'expirado' || estadoTransaccion === 'cancelado'"
                                        class="alert alert-danger text-start small mb-4">
                                        <strong>QR anulado o caducado.</strong><br>
                                        {{ mensajeEstado }} Genere un nuevo QR para volver a intentar el pago.
                                    </div>

                                    <div v-else-if="estadoTransaccion === 'revision'"
                                        class="alert alert-info text-start small mb-4">
                                        <strong>Pago en revisión.</strong><br>
                                        {{ mensajeEstado }} El sistema seguirá consultando el estado de la transacción.
                                    </div>

                                    <div v-else class="d-flex align-items-center justify-content-center mb-4 gap-2">
                                        <div class="spinner-grow text-primary spinner-grow-sm" role="status"></div>
                                        <span class="text-primary fw-bold small">{{ mensajeEstado || 'Esperando pago...' }}</span>
                                    </div>

                                    <div v-if="estadoPagoFacil" class="text-muted small mb-3">
                                        Estado PagoFácil: <strong>{{ estadoPagoFacil }}</strong> · Estado sistema: <strong>{{ estadoTransaccion }}</strong>
                                    </div>

                                    <!-- Contador de Tiempo -->
                                    <div class="mb-4">
                                        <h2 class="fw-bold text-primary display-6">
                                            {{ formatoTiempo(tiempoRestante) }}
                                        </h2>
                                    </div>

                                    <!-- Footer del Card: Total -->
                                    <div class="border-top pt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">Monto QR de prueba:</span>
                                            <span class="h4 fw-bold text-dark m-0">{{ formatoMoneda(props.montoQrPrueba || 0.01) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">Monto real de la venta:</span>
                                            <span class="fw-bold text-muted m-0">{{ formatoMoneda(props.montoRealSistema || ventaActual.total) }}</span>
                                        </div>
                                        <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                                            El código expira en 5 minutos.
                                        </small>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Footer Link -->
                        <div class="mt-4 text-center">
                            <Link :href="route('venta.index', props.venta?.id)"
                                class="text-decoration-none text-primary fw-bold small">
                            &larr; Cancelar y volver atrás
                            </Link>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>