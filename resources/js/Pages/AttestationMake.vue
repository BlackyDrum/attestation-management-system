<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {onMounted, ref} from "vue";

import Button from "primevue/button";

import combine from "@/CombinedData.js";

defineProps({
    attestations: {
        type: Object
    }
})

onMounted(() => {
    combinedData.value = combine(page.props.attestations);
    console.log(combinedData.value);
})

const page = usePage();

let combinedData = ref(null);
</script>

<template>
    <Head title="Make Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Make Attestations</h2>
        </template>
        <div class="mt-5 ml-5 text-gray-400 ">
            <Button @click="router.get('/attestations')" icon="pi pi-arrow-left" class="h-10" severity="secondary" label="Back"></Button>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    {{combinedData}}
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
