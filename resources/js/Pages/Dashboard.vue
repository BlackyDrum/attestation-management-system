<script setup>
import {Head, useForm, usePage} from '@inertiajs/vue3';
import {computed, onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';
import ToDoList from '@/Components/ToDoList.vue';
import ButtonBar from '@/Components/ButtonBar.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';

import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import Dropdown from 'primevue/dropdown';
import Chart from 'primevue/chart';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import ScrollPanel from 'primevue/scrollpanel';
import Textarea from 'primevue/textarea';

import combine from "@/CombinedData.js";
import checkPrivilege from "@/CheckPrivilege.js";
import {chartOptionsBar, setupChartDataBar} from "@/ChartDataBar.js";
import {chartOptionsPie, setupChartDataPie} from "@/ChartDataPie.js";

import * as Helper from "@/Helper.js";


defineProps({
    errors: {
        type: Array
    },
    semester: {
        type: Array
    },
})


const page = usePage();

const SCREEN_WIDTH_RESIZE = 1280;
const SEVERITIES = ref(["Info", "Error", "Warn", "Success"]);

const privileges = ref([]);
const notifications = ref([]);
const data = ref([]);
const isLoadingData = ref(false);
const users = ref([]);
const selectedSemester = ref(null);
const semester_id = ref(null);
const acronyms = ref([]);
const totalTaskCount = ref(0);
const totalCheckedCount = ref(0);
const combinedData = ref([]);
const showSendNotificationDialog = ref(false);
const userWithMatriculationNumber = ref([]);
const chartDataBarTotal = ref(null);
const chartDataPieTotal = ref(null);


const notificationForm = useForm({
    users: null,
    message: null,
    severity: null,
})

const chartOptionsBarTotal = ref(chartOptionsBar());

const chartOptionsPieTotal = ref(chartOptionsPie())


onMounted(() => {
    notifications.value = page.props.auth.notifications;
    privileges.value = page.props.auth.privileges;

    semester_id.value = localStorage.getItem('ams_dashboard_semester_id');
    if (semester_id.value)
        selectedSemester.value = JSON.parse(JSON.stringify(page.props.semester[page.props.semester.findIndex(item => item.id === parseInt(semester_id.value))]));

    if (selectedSemester.value)
        loadSemesterData();

    window.addEventListener('resize', handleWindowResize);
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;

    if (selectedSemester.value)
        handleSemesterSelection();
})

const disableNotificationFormButton = computed(() => {
    return notificationForm.processing || (!notificationForm.users || !notificationForm.severity || !notificationForm.message)
})

const handleWindowResize = () => {
    if (!selectedSemester.value) return;

    if (window.innerWidth > SCREEN_WIDTH_RESIZE) {
        chartOptionsPieTotal.value.aspectRatio = 1;
        return;
    }
    chartOptionsPieTotal.value.aspectRatio = 3;
}

const handleDialogSend = () => {
    notificationForm.severity = notificationForm.severity.toLowerCase();
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

const handleDialogOpen = () => {
    showSendNotificationDialog.value = true

    if (users.value.length !== 0) return;

    window.axios.get('/dashboard/users')
        .then(result => {
            users.value = result.data;
            userWithMatriculationNumber.value = Helper.getUsersWithMatriculationNumbers(users.value);
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
        })
}

const handleDialogClose = () => {
    showSendNotificationDialog.value = false;
    notificationForm.reset();
    page.props.errors = {};
}

const handleSemesterSelection = (event) => {
    if (parseInt(semester_id.value) === selectedSemester.value.id) return;

    localStorage.setItem('ams_dashboard_semester_id',selectedSemester.value.id);
    semester_id.value = selectedSemester.value.id;

    loadSemesterData()
}

const loadSemesterData = () => {
    isLoadingData.value = true;
    window.axios.get('/dashboard/data', {
        params: {
            semester_id: semester_id.value
        }
    })
        .then(result => {
            data.value = result.data;
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
            isLoadingData.value = false;

            acronyms.value = [];

            chartDataBarTotal.value = setupChartDataBar([],'Finished');
            chartDataPieTotal.value = setupChartDataPie(["Done", "To Do"], getComputedStyle(document.body));

            chartOptionsPieTotal.value.aspectRatio = window.innerWidth > SCREEN_WIDTH_RESIZE ? 1 : 3;

            combinedData.value = combine(data.value);
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
        })


    if (window.innerWidth < SCREEN_WIDTH_RESIZE) {
        chartDataBarTotal.value.labels = acronyms.value;
    }
}

const deleteNotification = (index, clear) => {
    axios.delete('/notifications', {
        data: {
            index: index,
            clearAll: clear,
        }
    })
        .then(response => {
            notifications.value.splice(index, 1);
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
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
                    <primary-button v-if="$page.props.auth.user.admin || checkPrivilege('can_send_notification', privileges)" @click="handleDialogOpen">Send
                        Notification
                    </primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex">
                    <Dropdown class="max-md:w-[16rem] w-80 ml-auto mb-4" placeholder="Select semester"
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
                <div class="flex" v-else-if="isLoadingData">
                    <div class="mx-auto">
                        <CustomProgressSpinner :processing="true"/>
                    </div>
                </div>
                <div v-else>
                    <div class="grid grid-cols-1 xl:grid-cols-[67%,30%] xl:gap-10">
                        <div class="dashboard__item">
                            <div class="flex h-full" v-if="combinedData.length === 0">
                                <div class="m-auto">
                                    <div class="text-gray-700 text-center">
                                        <div class="pi pi-chart-bar custom-icon"></div>
                                    </div>
                                    <div class="text-gray-500 text-center mt-4">
                                        No Data available
                                    </div>
                                </div>
                            </div>
                            <Chart v-else type="bar" :data="chartDataBarTotal" :options="chartOptionsBarTotal"/>
                        </div>
                        <div class="dashboard__item max-xl:mt-4">
                            <div class="flex h-full" v-if="combinedData.length === 0">
                                <div class="m-auto">
                                    <div class="text-gray-700 text-center">
                                        <div class="pi pi-chart-pie custom-icon"></div>
                                    </div>
                                    <div class="text-gray-500 text-center mt-4">
                                        No Data available
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <Chart type="doughnut" :data="chartDataPieTotal" :options="chartOptionsPieTotal"/>
                                <div class="mt-6">
                                    <ProgressBar
                                        :value="Math.round(totalCheckedCount / totalTaskCount * 100) || 0"></ProgressBar>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 mt-4 xl:grid-cols-[40%,57%] xl:gap-10">
                        <div class="dashboard__item">
                            <ToDoList/>
                        </div>
                        <div class="dashboard__item max-xl:mt-4">
                            <ScrollPanel class="custom-scroll-panel" v-if="notifications.length !== 0">
                                <div class="text-sm" v-for="(notification, index) in notifications" :key="notification">
                                    <Message :severity="notification.split('|')[0].trim().toLowerCase()"
                                             @close="deleteNotification(index, false)"
                                    >
                                        <div>{{ notification.split('|')[1].trim() }}</div>
                                        <div class="font-medium text-gray-900/70">
                                            {{ notification.split('|')[2].trim() }}
                                        </div>
                                    </Message>
                                </div>
                            </ScrollPanel>
                            <div v-else class="flex h-full">
                                <div class="mx-auto my-auto">
                                    <div class="text-gray-700 text-center">
                                        <div class="pi pi-envelope custom-icon"></div>
                                    </div>
                                    <div class="text-gray-500 text-center mt-4">
                                        No messages
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Notification Dialog -->
        <Dialog class="lg:w-[50%] md:w-[75%] w-[90%]"
                v-model:visible="showSendNotificationDialog" :closable="false" modal header="Send notification">
            <form @submit.prevent="handleDialogSend">
                <div class="p-inputgroup">
                    <span class="p-inputgroup-addon">
                        <i class="pi pi-user mr-2"></i>
                    </span>
                    <MultiSelect class="w-full md:w-20rem" placeholder="Users" :disabled="notificationForm.processing" :loading="users.length === 0"
                                 v-model="notificationForm.users" :options="userWithMatriculationNumber" filter
                                 optionLabel="name" :maxSelectedLabels="3"
                                 :virtualScrollerOptions="{ itemSize: 44 }"/>
                </div>
                <error-message :show="errors.users">
                    {{ errors.users }}
                </error-message>
                <span v-for="(error, key) in errors">
                    <error-message :show="true" v-if="key.includes('users.')">
                        {{ error }}
                    </error-message>
                </span>
                <div class=" mt-6">
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-tag mr-2"></i>
                        </span>
                        <Dropdown class="max-md:w-[16rem] w-80" placeholder="Severity"
                                  :disabled="notificationForm.processing" v-model="notificationForm.severity"
                                  :options="SEVERITIES"/>
                    </div>
                    <error-message :show="errors.severity">
                        {{ errors.severity }}
                    </error-message>
                </div>
                <div class="mt-6">
                    <Textarea class="w-full" placeholder="Write your message" :disabled="notificationForm.processing"
                              v-model="notificationForm.message" autoresize/>
                    <error-message :show="errors.message">
                        {{ errors.message }}
                    </error-message>
                </div>
                <ButtonBar @handle-close="handleDialogClose" :processing="notificationForm.processing" :disable_primary="disableNotificationFormButton">
                    <template #primary>
                        Send
                    </template>
                </ButtonBar>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard__item {
    @apply bg-white p-6 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg
}
.custom-icon {
    font-size: 10rem
}
.custom-scroll-panel {
    height: 24rem;
}
</style>
