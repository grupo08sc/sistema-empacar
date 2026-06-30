<template>
    <div class="rounded card shadow d-flex justify-content-center align-items-center" style="height: 50vh;">
        <div class="card-content">
            <p class="text-center text-lg font-weight-bold text-secondary">
                {{ mensaje }}
            </p>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        mensaje: {
            type: String,
            default: 'No tienes permisos para ver esta página.'
        }
    }
}
</script>