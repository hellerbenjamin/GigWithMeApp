<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Settings' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PagePlaceholder from '../../components/PagePlaceholder.vue';
</script>

<template>
    <Head title="Settings" />

    <PagePlaceholder
        icon="pi pi-cog"
        title="Settings"
        description="Your account and band preferences. Soon you'll update your profile, band details, and notification preferences right here."
    />
</template>
