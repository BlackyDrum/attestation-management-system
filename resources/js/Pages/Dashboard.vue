<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import {Head, router, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from "vue";

import Message from "primevue/message";

const page = usePage();

onMounted(() => {
    notifications.value = page.props.auth.notifications;
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;
})

const notifications = ref([]);

const deleteNotification = (index, clear) => {
    axios.delete('/notifications', {
        data: {
            index: index,
            clearAll: clear,
        }
    })
        .then(response => {
            if (clear) {
                notifications.value = [];
                router.reload('notifications');
            } else
                notifications.value.splice(index, 1);

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
                    <primary-button v-if="notifications.length !== 0" @click="deleteNotification(-1,true)">Clear all
                    </primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Message v-if="notifications.length !== 0" v-for="(notification, index) in notifications"
                         :key="notification" @close="deleteNotification(index, false)"
                         :severity="notification.split('|')[0].trim().toLowerCase()">
                    <h2>
                        Notification from {{ notification.split('|')[2].trim() }}
                    </h2>
                    <p class="font-medium">
                        {{ notification.split('|')[1].trim() }}
                    </p>
                </Message>
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
