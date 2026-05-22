<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Music' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PagePlaceholder from '../../components/PagePlaceholder.vue';
</script>

<template>
    <Head title="Music" />

    <PagePlaceholder
        icon="pi pi-volume-up"
        title="Music"
        description="Setlists and recordings. Soon you'll build setlists, attach tracks, and keep the band's whole repertoire in one place."
    />
</template>
