<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';

defineProps({
    users: {
        type: Object
    }
})

</script>

<template>
    <Head title="User" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-for="user in users.data" :key="user.id" class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <Card>
                        <template #content>
                            <div class="font-bold">
                                {{user.name}}
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <div class="text-white flex pb-5">
            <div class="mx-auto">
                <Link v-for="links in users.links" :href="links.url" class="p-3">
                    <span v-html="links.label" :class="{'bg-gray-600 p-2 rounded-xl': users.current_page == links.label}"></span>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
