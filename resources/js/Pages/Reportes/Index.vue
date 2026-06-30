<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import NoPermiso from '@/Components/NoPermiso.vue';

const props = defineProps({
    cantidadVentas: Number,
    cantidadVendida: String,
    cantidadClientes: Number,
    cantidadVisitas: Number,
    mes: Array,
    cantidad: Array,
    productosTop: Array,
    dias: Array,
    cantidadDias: Array
});

const page = usePage();
const user = page.props.auth.user;

const graficoTortaRef = ref(null);
const graficoTorta2Ref = ref(null);

const can = (funcionalidad) => {
    return page.props.auth.privilegios?.[funcionalidad]?.leer;
};

onMounted(() => {
    if (can('Reportes')) {
        renderGraficoMes();
        renderGraficoDias();
    }
});

const renderGraficoMes = () => {
    if (graficoTortaRef.value && props.mes && props.cantidad && props.mes.length > 0) {
        new window.Chart(graficoTortaRef.value, {
            type: 'pie',
            data: {
                labels: props.mes,
                datasets: [{
                    data: props.cantidad,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                    ],
                    borderWidth: 1,
                }]
            },
        });
    }
};

const renderGraficoDias = () => {
    if (graficoTorta2Ref.value && props.dias && props.cantidadDias && props.dias.length > 0) {
        new window.Chart(graficoTorta2Ref.value, {
            type: 'line',
            data: {
                labels: props.dias,
                datasets: [{
                    label: 'Ventas por Día',
                    data: props.cantidadDias,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
};
</script>


<template>

    <Head title="Reportes Dashboard" />

    <AppLayout>

        <section class="content" v-if="can('Reportes')">
            <div class="container-fluid">
                <div class="empacar-dashboard-hero mb-4">
                    <div>
                        <span class="hero-badge">EMPACAR S.A.</span>
                        <h2>Panel comercial sostenible</h2>
                        <p>
                            Ventas, cuotas, clientes, inventario y reportes alineados a una operación industrial de empaques y reciclaje.
                        </p>
                    </div>
                    <div class="hero-actions d-none d-md-flex">
                        <span><i class="fas fa-recycle"></i> Reciclaje</span>
                        <span><i class="fas fa-box-open"></i> Empaques</span>
                        <span><i class="fas fa-qrcode"></i> Pagos QR</span>
                    </div>
                </div>

                <div class="row empacar-metrics">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ props.cantidadVentas ?? 0 }}</h3>
                                <p>Ventas registradas</p>
                            </div>
                            <div class="icon"><i class="ion ion-bag"></i></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ props.cantidadVendida ?? 0 }}<sup style="font-size: 20px">Bs</sup>
                                </h3>
                                <p>Ingresos comerciales</p>
                            </div>
                            <div class="icon"><i class="ion ion-stats-bars"></i></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ props.cantidadClientes ?? 0 }}</h3>
                                <p>Clientes registrados</p>
                            </div>
                            <div class="icon"><i class="ion ion-person-add"></i></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ props.cantidadVisitas ?? 0 }}</h3>
                                <p>Visitas del sistema</p>
                            </div>
                            <div class="icon"><i class="ion ion-pie-graph"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <section class="col-lg-6 connectedSortable mb-4">
                        <div class="card bg-gradient h-100">
                            <div class="card-body d-flex flex-column align-items-center">
                                <h3 class="mb-3">VENTAS POR MES</h3><small class="chart-subtitle">Evolución comercial mensual</small>

                                <div v-if="props.mes && props.mes.length > 0">
                                    <canvas ref="graficoTortaRef" id="graficoTorta"></canvas>
                                </div>
                                <p v-else class="text-muted">No hay datos de ventas por mes.</p>
                            </div>
                        </div>
                    </section>
                    <section class="col-lg-6 connectedSortable">
                        <div class="card bg-gradient">
                            <div class="card-body d-flex flex-column align-items-center">
                                <h3 class="mb-3">VENTAS POR DÍA</h3><small class="chart-subtitle">Seguimiento diario de operaciones</small>

                                <div v-if="props.dias && props.dias.length > 0">
                                    <canvas ref="graficoTorta2Ref" id="graficoTorta2"></canvas>
                                </div>
                                <p v-else class="text-muted">No hay datos de ventas por día.</p>
                            </div>
                        </div>
                    </section>
                    <section class="col-lg-12 connectedSortable mb-4">
                        <div class="card bg-gradient h-100">
                            <div class="card-body d-flex flex-column">
                                <h3 class="text-center mb-3">PRODUCTOS TOP</h3><small class="chart-subtitle text-center mb-3">Productos con mayor movimiento comercial</small>

                                <div v-if="props.productosTop && props.productosTop.length > 0"
                                    class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th scope="col">Producto</th>
                                                <th scope="col">Unidades</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-group-divider">
                                            <tr v-for="producto in props.productosTop" :key="producto.id">
                                                <td>{{ producto.producto }}</td>
                                                <td>{{ producto.total_vendido }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-center text-muted pb-3 mt-auto mb-auto">
                                    No hay productos registrados.
                                </p>
                            </div>
                        </div>
                    </section>

                </div>

            </div>
        </section>

        <NoPermiso v-else mensaje="No tienes permisos para ver este reporte." />

    </AppLayout>
</template>

<style scoped>
.empacar-dashboard-hero {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    padding: 1.4rem;
    border-radius: 24px;
    background:
        radial-gradient(circle at 90% 10%, rgba(141,198,63,.28), transparent 28%),
        linear-gradient(135deg, #006B3F, #006BA6);
    color: #FFFFFF;
    box-shadow: 0 18px 40px rgba(0,107,63,.22);
}

.hero-badge {
    display: inline-flex;
    padding: .35rem .75rem;
    border-radius: 999px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.28);
    font-weight: 900;
    letter-spacing: .08rem;
    font-size: .76rem;
    margin-bottom: .6rem;
}

.empacar-dashboard-hero h2 {
    font-weight: 900;
    margin-bottom: .35rem;
}

.empacar-dashboard-hero p {
    margin: 0;
    max-width: 720px;
    color: rgba(255,255,255,.88);
}

.hero-actions {
    gap: .5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.hero-actions span {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 999px;
    padding: .55rem .85rem;
    font-weight: 800;
    white-space: nowrap;
}

.empacar-metrics .small-box {
    min-height: 130px;
}

.card h3 {
    color: #063B2B;
    font-weight: 900;
    letter-spacing: .02rem;
}

.chart-subtitle {
    color: #5D756A;
    display: block;
    margin-top: -.65rem;
}

body.tema-5 .card h3,
body.tema-5 .chart-subtitle {
    color: #F0FDF4 !important;
}
</style>
