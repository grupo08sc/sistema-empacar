<script setup>
import { onMounted, ref } from "vue";
import { Chart, registerables } from "chart.js";
import AppLayout from "@/Layouts/AppLayout.vue";

Chart.register(...registerables);

const props = defineProps({
    datos: {
        type: Array,
        default: () => []
    },
    recursosMasAccedidos: {
        type: Array,
        default: () => []
    }
});

const chartRef = ref(null);
let chartInstance = null;

onMounted(() => {
    const nombres = props.datos.map(d => d.nombre);
    const visitas = props.datos.map(d => d.visitas);

    const ctx = chartRef.value.getContext("2d");

    chartInstance = new Chart(ctx, {
        type: "bar",
        data: {
            labels: nombres,
            datasets: [
                {
                    label: "Visitas por página",
                    data: visitas,
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0,
                },
            },
        },
    });
});
</script>

<template>
    <AppLayout>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estadísticas de acceso por página</h3>
                </div>
                <div class="card-body">
                    <canvas ref="chartRef"></canvas>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recursos más accedidos por bitácora</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Recurso</th>
                                        <th>Total de accesos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in recursosMasAccedidos" :key="item.recurso">
                                        <td>{{ item.recurso }}</td>
                                        <td>{{ item.total }}</td>
                                    </tr>
                                    <tr v-if="recursosMasAccedidos.length === 0">
                                        <td colspan="2" class="text-center text-muted">Sin registros de acceso todavía.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Contador general por página</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Página</th>
                                        <th>Visitas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in datos" :key="item.nombre">
                                        <td>{{ item.nombre }}</td>
                                        <td>{{ item.visitas }}</td>
                                    </tr>
                                    <tr v-if="datos.length === 0">
                                        <td colspan="2" class="text-center text-muted">No hay visitas registradas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
