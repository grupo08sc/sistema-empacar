<script setup>
import { reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NoPermiso from '@/Components/NoPermiso.vue';

const props = defineProps({
    auditorias: Array,
    modulos: Array,
    niveles: Array,
    filtros: Object,
});

const page = usePage();
const can = (funcionalidad) => page.props.auth.privilegios?.[funcionalidad]?.leer;

const filtrosForm = reactive({
    modulo: props.filtros?.modulo ?? '',
    accion: props.filtros?.accion ?? '',
    nivel: props.filtros?.nivel ?? '',
    fecha_inicio: props.filtros?.fecha_inicio ?? '',
    fecha_fin: props.filtros?.fecha_fin ?? '',
});

const detalleActivo = ref(null);

const filtrar = () => {
    router.get(route('auditoria.index'), filtrosForm, {
        preserveScroll: true,
        replace: true,
    });
};

const limpiar = () => {
    filtrosForm.modulo = '';
    filtrosForm.accion = '';
    filtrosForm.nivel = '';
    filtrosForm.fecha_inicio = '';
    filtrosForm.fecha_fin = '';
    router.get(route('auditoria.index'), {}, {
        preserveScroll: true,
        replace: true,
    });
};

const badgeClass = (nivel) => {
    const n = String(nivel ?? '').toLowerCase();
    if (n === 'warning') return 'badge badge-warning';
    if (n === 'error' || n === 'critical') return 'badge badge-danger';
    if (n === 'success') return 'badge badge-success';
    return 'badge badge-info';
};

const stringify = (value) => JSON.stringify(value ?? {}, null, 2);
</script>

<template>
    <Head title="Auditoría" />

    <AppLayout>
        <section class="content" v-if="can('Auditoria')">
            <div class="container-fluid">
                <div class="card card-outline card-dark">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history mr-2"></i>
                            Auditoría de acciones críticas
                        </h3>
                    </div>
                    <div class="card-body">
                        <form class="row align-items-end" @submit.prevent="filtrar">
                            <div class="col-md-2">
                                <label>Módulo</label>
                                <select v-model="filtrosForm.modulo" class="form-control">
                                    <option value="">Todos</option>
                                    <option v-for="modulo in modulos" :key="modulo" :value="modulo">{{ modulo }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Nivel</label>
                                <select v-model="filtrosForm.nivel" class="form-control">
                                    <option value="">Todos</option>
                                    <option v-for="nivel in niveles" :key="nivel" :value="nivel">{{ nivel }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Acción</label>
                                <input v-model="filtrosForm.accion" type="text" class="form-control" placeholder="crear, anular..." />
                            </div>
                            <div class="col-md-2">
                                <label>Desde</label>
                                <input v-model="filtrosForm.fecha_inicio" type="date" class="form-control" />
                            </div>
                            <div class="col-md-2">
                                <label>Hasta</label>
                                <input v-model="filtrosForm.fecha_fin" type="date" class="form-control" />
                            </div>
                            <div class="col-md-2 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search mr-1"></i> Filtrar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" @click="limpiar">
                                    Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Últimos registros</h3>
                        <div class="card-tools text-muted">Máximo mostrado: 300 eventos</div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Módulo</th>
                                    <th>Acción</th>
                                    <th>Nivel</th>
                                    <th>Entidad</th>
                                    <th>Descripción</th>
                                    <th>IP</th>
                                    <th>Navegador</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in auditorias" :key="log.id">
                                    <td>{{ log.fecha }}</td>
                                    <td>{{ log.usuario }}</td>
                                    <td>{{ log.modulo }}</td>
                                    <td><code>{{ log.accion }}</code></td>
                                    <td><span :class="badgeClass(log.nivel)">{{ log.nivel }}</span></td>
                                    <td>{{ log.entidad_tipo ?? '-' }} #{{ log.entidad_id ?? '-' }}</td>
                                    <td>{{ log.descripcion }}</td>
                                    <td>{{ log.ip ?? '-' }}</td>
                                    <td class="small text-truncate" style="max-width: 220px;">{{ log.user_agent ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-outline-dark" @click="detalleActivo = log">
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!auditorias?.length">
                                    <td colspan="10" class="text-center text-muted py-4">No hay eventos de auditoría con los filtros seleccionados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="detalleActivo" class="card card-outline card-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Detalle de auditoría #{{ detalleActivo.id }}</h3>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="detalleActivo = null">Cerrar</button>
                    </div>
                    <div class="card-body">
                        <p><strong>Descripción:</strong> {{ detalleActivo.descripcion }}</p>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Estado anterior</h5>
                                <pre class="bg-light p-3 rounded small">{{ stringify(detalleActivo.estado_anterior) }}</pre>
                            </div>
                            <div class="col-md-6">
                                <h5>Estado nuevo</h5>
                                <pre class="bg-light p-3 rounded small">{{ stringify(detalleActivo.estado_nuevo) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <NoPermiso v-else mensaje="No tienes permisos para ver la auditoría del sistema." />
    </AppLayout>
</template>
