<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';

import Message from 'primevue/message';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Avatar from 'primevue/avatar';

import combine from "@/CombinedData.js";


defineProps({
    users: {
        type: Array
    },
    errors: {
        type: Array
    },
    semester: {
        type: Array
    },
    data: {
        type: Array
    }
})


const page = usePage();

const notifications = ref([]);
const selectedSemester = ref(null);
const acronyms = ref([]);
const combinedData = ref(null);
const showSendNotificationDialog = ref(false);
const userWithMatriculationNumber = ref([]);
const chartDataBarTotal = ref(null);
const chartDataPieTotal = ref(null);

const notificationForm = useForm({
    users: null,
    message: null,
    severity: null,
})

const severities = ref(["info", "error", "warn", "success"]);

const chartOptionsBarTotal = ref({
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        },
    },
});

const chartOptionsPieTotal = ref({
    plugins: {
        legend: {
            display: true,
        },
    }
})

onMounted(() => {
    notifications.value = page.props.auth.notifications;

    userWithMatriculationNumber.value = page.props.users;
    userWithMatriculationNumber.value = userWithMatriculationNumber.value.slice().sort((a, b) => {
        const surnameA = a.name.split(' ').slice(-1)[0];
        const surnameB = b.name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });
    userWithMatriculationNumber.value.map(user => {
        user.name = `${user.name} (${user.matriculation_number})`;
    })
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;

    if (selectedSemester.value) {
        handleSemesterSelection();
    }
})

const setupChartDataBar = () => {
    return {
        labels: [],
        datasets: [
            {
                label: 'Checked Tasks',
                data: [],
                backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                borderColor: ['rgb(255, 159, 64)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)'],
                borderWidth: 1
            }
        ]
    }
}

const setupChartDataPie = () => {
    const documentStyle = getComputedStyle(document.body);

    return {
        labels:["Done", "To Do"],
        datasets: [
            {
                data: [0, 0],
                backgroundColor: [documentStyle.getPropertyValue('--blue-500'), documentStyle.getPropertyValue('--yellow-500'), documentStyle.getPropertyValue('--green-500')],
                hoverBackgroundColor: [documentStyle.getPropertyValue('--blue-400'), documentStyle.getPropertyValue('--yellow-400'), documentStyle.getPropertyValue('--green-400')]
            }
        ]
    };
}

const handleDialogSend = () => {
    notificationForm.post('/notifications', {
        onSuccess: () => {
            notificationForm.reset();
            showSendNotificationDialog.value = false;
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Notification sent',
                life: 3000,
            })
        },
    })
}

const handleDialogClose = () => {
    showSendNotificationDialog.value = false;
    notificationForm.reset();
    page.props.errors = {};
}

const handleSemesterSelection = (event) => {
    acronyms.value = [];

    chartDataBarTotal.value = setupChartDataBar();
    chartDataPieTotal.value = setupChartDataPie();

    combinedData.value = combine(page.props.data);
    combinedData.value = combinedData.value.filter(item => item.semester_id === selectedSemester.value.id);

    let totalTasks = 0;
    let totalChecked = 0;
    for (const subject of combinedData.value) {
        chartDataBarTotal.value.labels.push(subject.subject_name);
        acronyms.value.push(subject.acronym ?? subject.subject_name);

        let checkedCount = 0;
        for (const task of subject.tasks[0]) {
            totalTasks++;
            if (task.checked) {
                checkedCount++;
                totalChecked++;
            }
        }
        chartDataBarTotal.value.datasets[0].data.push(checkedCount);
    }

    chartDataPieTotal.value.datasets[0].data[0] = totalChecked;
    chartDataPieTotal.value.datasets[0].data[1] = totalTasks - totalChecked;

    if (window.innerWidth < 768) {
        console.log("asd")
        chartDataBarTotal.value.labels = acronyms.value;
    }
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
                    <primary-button v-if="$page.props.auth.user.admin" @click="showSendNotificationDialog = true">Send
                        Notification
                    </primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex">
                    <Dropdown v-model="selectedSemester" @change="handleSemesterSelection($event)"
                              :options="semester" optionLabel="semester"
                              class="max-md:w-[16rem] w-80 ml-auto mb-4" placeholder="Select semester"/>
                </div>
                <div v-if="!selectedSemester" class="mt-4">
                    <div class="text-gray-700 text-center">
                        <div style="font-size: 10rem" class="pi pi-chart-line"></div>
                    </div>
                    <div class="text-gray-500 text-center mt-4">
                        Please select a semester to access your dashboard
                    </div>
                </div>
                <div v-else-if="combinedData.length === 0">
                    <div class="text-gray-700 text-center">
                        <div style="font-size: 10rem" class="pi pi-ban"></div>
                    </div>
                    <div class="text-gray-500 text-center mt-4">
                        No Data available
                    </div>
                </div>
                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="grid xl:grid-cols-[70%,30%] grid-cols-1 gap-2">
                        <div class="border rounded p-2">
                            <Chart type="bar" :data="chartDataBarTotal" :options="chartOptionsBarTotal" />
                        </div>
                        <div class="max-xl:mt-4 self-center py-10 max-xl:w-[30%] border rounded p-2">
                            <Chart type="doughnut" :data="chartDataPieTotal" :options="chartOptionsPieTotal"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <Dialog v-model:visible="showSendNotificationDialog" :closable="false" modal header="Send notification"
                class="lg:w-[50%] md:w-[75%] w-[90%]">
            <form @submit.prevent="handleDialogSend">
                <span class="p-float-label mt-5">
                    <MultiSelect :disabled="notificationForm.processing" :loading="!$props.users"
                                 v-model="notificationForm.users" :options="userWithMatriculationNumber" filter
                                 optionLabel="name" :maxSelectedLabels="3"
                                 :virtualScrollerOptions="{ itemSize: 44 }"
                                 class="w-full md:w-20rem"/>
                    <label for="users">Users</label>
                </span>
                <error-message :show="errors.users">
                    {{ errors.users }}
                </error-message>

                <span v-for="(error, key) in errors">
                    <error-message :show="true" v-if="key.includes('users.')">
                        {{ error }}
                    </error-message>
                </span>
                <div class=" mt-6">
                    <span class="p-float-label">
                        <Dropdown :disabled="notificationForm.processing" v-model="notificationForm.severity"
                                  :options="severities"
                                  class="max-md:w-[16rem] w-80"/>
                        <label>Severity</label>
                    </span>
                    <error-message :show="errors.severity">
                        {{ errors.severity }}
                    </error-message>
                </div>
                <div class="mt-6">
                    <span class="p-float-label">
                        <InputText class="w-full" :disabled="notificationForm.processing"
                                   v-model="notificationForm.message" autoresize/>
                        <label>Message</label>
                    </span>
                    <error-message :show="errors.message">
                        {{ errors.message }}
                    </error-message>
                </div>

                <div class="my-4 grid grid-cols-2">
                    <div class="justify-center">
                        <CustomProgressSpinner :processing="notificationForm.processing"></CustomProgressSpinner>
                    </div>
                    <div class="flex justify-end" style="height: 3rem">
                        <primary-button class="mr-5 disabled:cursor-not-allowed"
                                        :disabled="notificationForm.processing || (!notificationForm.users || !notificationForm.severity || !notificationForm.message)">
                            Send
                        </primary-button>
                        <secondary-button @click="handleDialogClose">Cancel</secondary-button>
                    </div>
                </div>
            </form>
        </Dialog>

    </AuthenticatedLayout>
</template>
