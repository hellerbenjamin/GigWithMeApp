<script setup>
import { ref } from 'vue';

// Button and Card are auto-imported by unplugin-vue-components via the
// PrimeVue resolver — no explicit import needed.
const count = ref(0);
</script>

<template>
    <Card class="max-w-md mx-auto mt-12">
        <template #title>PrimeVue is wired up</template>
        <template #content>
            <p class="mb-4">You clicked {{ count }} times.</p>
            <Button label="Click me" icon="pi pi-check" @click="count++" />
        </template>
    </Card>
</template>
