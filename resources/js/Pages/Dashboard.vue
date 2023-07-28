<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                </div>
            </div>
        </div>


        <div class="text-gray-700 text-center">
            <div style="font-size: 10rem" class="pi pi-bell"></div>
        </div>
        <div class="text-gray-500 text-center mt-4">
            You have no notifications
        </div>


    </AuthenticatedLayout>
</template>
