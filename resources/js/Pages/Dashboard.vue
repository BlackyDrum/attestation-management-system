<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref, watch} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';
import ToDoList from '@/Components/ToDoList.vue';

import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Chart from 'primevue/chart';
import ProgressSpinner from 'primevue/progressspinner';
import ProgressBar from 'primevue/progressbar';

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
    },
    selected_semester: {
        type: Array
    }
})


const page = usePage();

const SCREEN_WIDTH_RESIZE = 1280;
const notifications = ref([]);
const selectedSemester = ref(null);
const acronyms = ref([]);
const totalTaskCount = ref(0);
const totalCheckedCount = ref(0);
const combinedData = ref([]);
const showSendNotificationDialog = ref(false);
const userWithMatriculationNumber = ref([]);
const chartDataBarTotal = ref(null);
const chartDataPieTotal = ref(null);
const loadingData = ref(false);

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
    responsive: true,
});

const chartOptionsPieTotal = ref({
    plugins: {
        legend: {
            display: true,
        },
    },
    responsive: true,
    aspectRatio: 1,
})


onMounted(() => {
    notifications.value = page.props.auth.notifications;
    selectedSemester.value = page.props.selected_semester;

    if (selectedSemester.value) {
        loadSemesterData();
    }

    userWithMatriculationNumber.value = page.props.users;
    userWithMatriculationNumber.value = userWithMatriculationNumber.value.slice().sort((a, b) => {
        const surnameA = a.name.split(' ').slice(-1)[0];
        const surnameB = b.name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });
    userWithMatriculationNumber.value.map(user => {
        user.name = `${user.name} (${user.matriculation_number})`;
    })

    window.addEventListener('resize', handleResize);
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;

    if (selectedSemester.value) {
        handleSemesterSelection();
    }
})

const handleResize = () => {
    if (!selectedSemester.value) return;

    if (window.innerWidth > SCREEN_WIDTH_RESIZE) {
        chartOptionsPieTotal.value.aspectRatio = 1;
        return;
    }
    chartOptionsPieTotal.value.aspectRatio = 3;
}

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
    loadingData.value = true;
    axios.patch('/dashboard',{
        semester: selectedSemester.value.id,
    })
        .then(response => {
            loadSemesterData();
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
        })
        .then(() => {
            loadingData.value = false;
        })
}

const loadSemesterData = () => {
    acronyms.value = [];

    chartDataBarTotal.value = setupChartDataBar();
    chartDataPieTotal.value = setupChartDataPie();

    chartOptionsPieTotal.value.aspectRatio = window.innerWidth > SCREEN_WIDTH_RESIZE ? 1 : 3;

    combinedData.value = combine(page.props.data);
    combinedData.value = combinedData.value.filter(item => item.semester_id === selectedSemester.value.id);

    let totalTasks = 0;
    let totalChecked = 0;
    for (const subject of combinedData.value) {
        chartDataBarTotal.value.labels.push(subject.acronym);
        acronyms.value.push(subject.acronym);

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

    totalTaskCount.value = totalTasks;
    totalCheckedCount.value = totalChecked;

    if (window.innerWidth < SCREEN_WIDTH_RESIZE) {
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
                    <Dropdown  class="max-md:w-[16rem] w-80 ml-auto mb-4" placeholder="Select semester"
                               v-model="selectedSemester" @change="handleSemesterSelection($event)"
                              :options="semester" optionLabel="semester"/>
                </div>
                <div class="mt-4" v-if="!selectedSemester">
                    <div class="text-gray-700 text-center">
                        <div class="pi pi-chart-line custom-icon"></div>
                    </div>
                    <div class="text-gray-500 text-center mt-4">
                        Please select a semester to access your dashboard
                    </div>
                </div>
                <div class="flex" v-else-if="loadingData">
                    <div class="mx-auto">
                        <ProgressSpinner class="custom-progress-spinner" />
                    </div>
                </div>
                <div v-else-if="combinedData.length === 0">
                    <div class="text-gray-700 text-center">
                        <div class="pi pi-ban custom-icon"></div>
                    </div>
                    <div class="text-gray-500 text-center mt-4">
                        No Data available
                    </div>
                </div>
                <div v-else>
                    <div class="grid grid-cols-1 xl:grid-cols-[67%,30%] xl:gap-10">
                        <div class="bg-white p-6 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <Chart type="bar" :data="chartDataBarTotal" :options="chartOptionsBarTotal" />
                        </div>
                        <div class="bg-white p-6 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg max-xl:mt-4">
                            <Chart type="doughnut" :data="chartDataPieTotal" :options="chartOptionsPieTotal"/>
                            <div class="mt-6">
                                <ProgressBar :value="Math.round(totalCheckedCount / totalTaskCount * 100)"></ProgressBar>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 mt-4 xl:grid-cols-[40%,50%] xl:gap-10">
                        <div class="bg-white p-6 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <ToDoList/>
                        </div>
                        <div></div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog class="lg:w-[50%] md:w-[75%] w-[90%]"
                v-model:visible="showSendNotificationDialog" :closable="false" modal header="Send notification">
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
                        <Dropdown class="max-md:w-[16rem] w-80"
                                  :disabled="notificationForm.processing" v-model="notificationForm.severity"
                                  :options="severities"/>
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
                    <div class="flex justify-end footer__buttonbar">
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

<style scoped>
.custom-progress-spinner {
    width: 10vw;
    height: 10vw
}
.custom-icon {
    font-size: 10rem
}
.footer__buttonbar {
    height: 3rem
}
@media (max-width: 1280px) {
    .custom-progress-spinner {
        width: 25vw;
        height: 25vw
    }
}
</style>
