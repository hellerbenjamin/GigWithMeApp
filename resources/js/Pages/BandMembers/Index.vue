<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Band Members' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PagePlaceholder from '../../components/PagePlaceholder.vue';
</script>

<template>
    <Head title="Band Members" />

    <PagePlaceholder
        icon="pi pi-users"
        title="Band Members"
        description="Your roster. Soon you'll invite bandmates, set their roles, and control who can book shows on the band's behalf."
    />
</template>
