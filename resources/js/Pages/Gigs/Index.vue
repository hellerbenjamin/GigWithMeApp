<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Gigs' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PagePlaceholder from '../../components/PagePlaceholder.vue';
</script>

<template>
    <Head title="Gigs" />

    <PagePlaceholder
        icon="pi pi-calendar"
        title="Gigs"
        description="Your gig calendar. Soon you'll book shows, track confirmations, and see every date coming up — Roadie keeps the schedule straight."
    />
</template>
