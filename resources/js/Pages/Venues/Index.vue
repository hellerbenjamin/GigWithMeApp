<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Venues' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PagePlaceholder from '../../components/PagePlaceholder.vue';
</script>

<template>
    <Head title="Venues" />

    <PagePlaceholder
        icon="pi pi-map-marker"
        title="Venues"
        description="Your little black book of rooms. Soon you'll save venues with their contacts and reuse them in a tap whenever you book a gig."
    />
</template>
