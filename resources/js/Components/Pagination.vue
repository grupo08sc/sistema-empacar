<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
});
</script>

<template>
    <ul class="pagination pagination-sm m-0 float-right" v-if="links.length > 3">
        <li v-for="(link, key) in links" :key="key" class="page-item"
            :class="{ 'active': link.active, 'disabled': !link.url }">

            <div v-if="link.url === null" class="page-link" v-html="link.label"></div>

            <Link v-else class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
        </li>
    </ul>
</template>