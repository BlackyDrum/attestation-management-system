<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';

import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from "vue";

import Message from "primevue/message";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import MultiSelect from "primevue/multiselect";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";

defineProps({
    users: {
        type: Array,
    },
    errors: {
        type: Array
    }
})

const page = usePage();

onMounted(() => {
    notifications.value = page.props.auth.notifications;
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;
})

const notifications = ref([]);
const showDialog = ref(false);

const notificationForm = useForm({
    users: null,
    message: null,
    severity: null,
})

const severities = ref(["info", "error", "warn", "success"]);

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
const handleDialogSend = () => {
    notificationForm.post('/notifications', {
        onSuccess: () => {
            notificationForm.reset();
            showDialog.value = false;
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Notification sent',
                life: 3000,
            })
        }
    })
}

const handleDialogClose = () => {
    showDialog.value = false;
    notificationForm.reset();
    page.props.errors = {};
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
                    <primary-button v-if="$page.props.auth.user.admin" @click="showDialog = true">Send Notification
                    </primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-end">
                    <Button icon="pi pi-trash" severity="danger"
                            v-if="notifications.length !== 0" @click="deleteNotification(-1,true)" label="Clear All"/>

                </div>
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

        <Dialog v-model:visible="showDialog" :closable="false" modal header="Send notification" :style="{ width: '90vw' }">
            <form @submit.prevent="handleDialogSend">
                <span class="p-float-label mt-5">
                    <MultiSelect :disabled="notificationForm.processing" :loading="!$props.users"
                                 v-model="notificationForm.users" :options="users" filter
                                 optionLabel="name" :maxSelectedLabels="3"
                                 :virtualScrollerOptions="{ itemSize: 44 }"
                                 class="w-full md:w-20rem"/>
                    <label for="users">Users</label>
                </span>
                <div v-if="errors.users" class="text-red-600 font-medium">
                    {{ errors.users }}
                </div>
                <div class=" mt-6">
                    <span class="p-float-label">
                        <Dropdown :disabled="notificationForm.processing" v-model="notificationForm.severity"
                                  :options="severities"
                                  class="max-md:w-[16rem] w-80"/>
                        <label>Severity</label>
                    </span>
                    <div v-if="errors.severity" class="text-red-600 font-medium">
                        {{ errors.severity }}
                    </div>
                </div>
                <div class="mt-6">
                    <span class="p-float-label">
                        <InputText class="w-full" :disabled="notificationForm.processing" v-model="notificationForm.message" autoresize />
                        <label>Message</label>
                    </span>
                    <div v-if="errors.message" class="text-red-600 font-medium">
                        {{ errors.message }}
                    </div>
                </div>

                <div class="my-4 grid grid-cols-2">
                    <div class="justify-center">
                        <CustomProgressSpinner :processing="notificationForm.processing"></CustomProgressSpinner>
                    </div>
                    <div class="flex justify-end" style="height: 3rem">
                        <primary-button class="mr-5" :disabled="notificationForm.processing">Send</primary-button>
                        <secondary-button @click="handleDialogClose">Cancel</secondary-button>
                    </div>
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
