<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    data: Array,
});

</script>

<template>

    <Head title="Resultados de Búsqueda" />
    <AppLayout>
        <div class="card-header">
            <h1 class="card-title">
                <i class="fas fa-search mr-1"></i>
                <b>RESULTADO DE LA BÚSQUEDA</b>
            </h1>
        </div>

        <div class="card table-responsive">
            <div class="card-body">
                <table class="table table-hover" id="menus">
                    <thead>
                        <tr>
                            <th>RUTA</th>
                            <th>COINCIDENCIAS</th>
                            <th>TABLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(d, index) in data" :key="d.id || index">
                            <td>
                                <Link :href="d.ruta" class="text-primary">
                                Ruta
                                </Link>
                            </td>
                            <td>{{ d.nombre }}</td>
                            <td>{{ d.modelo }}</td>
                        </tr>

                        <tr v-if="data.length === 0">
                            <td colspan="3" class="text-center">No se encontraron resultados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>