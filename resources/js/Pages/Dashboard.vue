<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import {Head, router, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from "vue";

import Card from 'primevue/card';

const page = usePage();

onMounted(() => {
    notifications.value = page.props.auth.notifications;
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;
})

const notifications = ref([]);

const deleteNotification = (index, clear) => {
    window.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: "Removing notification...",
        life: 3000,
    })

    axios.delete('/dashboard', {
        data: {
            index: index,
            clearAll: clear,
        }
    })
        .then(response => {
            if (clear) notifications.value = [];
            else notifications.value.splice(index, 1);
            router.reload('notifications');
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: response.data,
                life: 3000,
            })
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 3000,
            })
        })
}
</script>

<template>
    <Head title="Dashboard"/>


    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Dashboard
                    </h2>
                </div>
                <div class="ml-auto">
                    <primary-button v-if="notifications.length !== 0" @click="deleteNotification(-1,true)">Clear all</primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="notifications.length !== 0" v-for="(notification, index) in notifications" :key="index" class="mb-4 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <Card>
                        <template #title>
                            <div class="grid grid-cols-[90%,10%]">
                                <div>
                                    Notification from {{notification.split('|')[2].trim()}}
                                </div>
                                <div class="ml-auto">
                                    <button @click="deleteNotification(index, false)">
                                        <i class="pi pi-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <p class="font-medium">
                                {{notification.split('|')[1].trim()}}
                            </p>
                        </template>
                    </Card>
                </div>
            </div>
        </div>



        <div v-if="notifications.length === 0">
            <div class="text-gray-700 text-center">
                <div style="font-size: 10rem" class="pi pi-bell"></div>
            </div>
            <div class="text-gray-500 text-center mt-4">
                You have no notifications
            </div>
        </div>


    </AuthenticatedLayout>
</template>
