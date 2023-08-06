<script setup>
import {Head, useForm, usePage, router} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from "vue";

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';

import {useConfirm} from "primevue/useconfirm";
import Dialog from "primevue/dialog";
import MultiSelect from 'primevue/multiselect';
import InputText from "primevue/inputtext";
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from "primevue/checkbox";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Editor from 'primevue/editor';
import Chart from 'primevue/chart';

import combine from "@/CombinedData.js";
import reduce_tasks from "@/ReduceTasks.js";


defineProps({
    users: {
        type: Object
    },
    semester: {
        type: Object
    },
    attestations: {
        type: Array
    },
    errors: {
        type: Object
    }
})


const page = usePage();
const confirm = useConfirm();

const showDialog = ref(false);
const showAttestation = ref(false);
const isEdit = ref(false);
const taskCount = ref(1);
const combinedData = ref(null);
const successForm = ref(false);
const subject_name = ref("");
const tasks = ref([]);
const userData = ref([]);
const userWithMatriculationNumber = ref([]);
const headers = ref(null);
const descriptions = ref([]);
const chartData = ref([]);
const chartOptions = ref({
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 5
            },
        }
    }
});

const attestationForm = useForm({
    id: null,
    users: null,
    subjectNumber: null,
    subjectName: null,
    semester: null,
    attestations: [],
})

const colors = ref([
    {rgb: "rgb(0, 0, 0)", label: "Black"},
    {rgb: "rgb(255, 255, 255)", label: "White"},
    {rgb: "rgb(255, 0, 0)", label: "Red"},
    {rgb: "rgb(0, 255, 0)", label: "Green"},
    {rgb: "rgb(0, 0, 255)", label: "Blue"},
    {rgb: "rgb(255, 165, 0)", label: "Orange"},
    {rgb: "rgb(128, 0, 128)", label: "Purple"},
    {rgb: "rgb(255, 255, 0)", label: "Yellow"},
    {rgb: "rgb(0, 128, 128)", label: "Teal"},
    {rgb: "rgb(128, 128, 0)", label: "Olive"},
    {rgb: "rgb(128, 0, 0)", label: "Maroon"},
]);


onMounted(() => {
    combinedData.value = combine(page.props.attestations);

    userWithMatriculationNumber.value = page.props.users;
    userWithMatriculationNumber.value = userWithMatriculationNumber.value.slice().sort((a, b) => {
        const surnameA = a.name.split(' ').slice(-1)[0];
        const surnameB = b.name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });
    userWithMatriculationNumber.value.map(user => {
        user.name = `${user.name} (${user.matriculation_number})`;
    })

    chartData.value = [];
    setupChart();
})

onBeforeUpdate(() => {
    combinedData.value = combine(page.props.attestations);

    chartData.value = [];
    setupChart();
})

const setupChart = () => {
    for (let i = 0; i < combinedData.value.length; i++) {
        chartData.value.push({
            labels: [],
            datasets: [
                {
                    label: 'Checked',
                    data: [],
                    backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                    borderColor: ['rgb(255, 159, 64)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)'],
                    borderWidth: 1
                }
            ]
        })
        for (const item of combinedData.value[i].tasks[0]) {
            chartData.value[i].labels.push(item.title)
            chartData.value[i].datasets[0].data.push(0);
        }
        for (const user of combinedData.value[i].tasks) {
            for (const item of user) {
                if (item.checked) {
                    let index = chartData.value[i].labels.findIndex((label) => label === item.title);
                    chartData.value[i].datasets[0].data[index]++;
                }
            }
        }
    }
}

const handleDialogOpen = () => {
    reset();
    showDialog.value = true;
    isEdit.value = false;
}

const handleDialogClose = () => {
    reset();
    showDialog.value = false;
    isEdit.value = false;
}

const handleForm = () => {
    if (!isEdit.value) {
        attestationForm
            .transform((data) => ({
                ...data,
                semester: data.semester ? data.semester.semester : null,
            }))
            .post('/attestations', {
                onSuccess: () => {
                    reset();
                    showDialog.value = false;
                    successForm.value = true;
                    combinedData.value = combine(page.props.attestations);
                    window.toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'New Subject for attestation created',
                        life: 3000,
                    })
                },
                onError: (error) => {
                    for (const e in error) {
                        attestationForm.reset(e)
                    }
                }
            })
        return;
    }
    attestationForm
        .transform((data) => ({
            ...data,
            semester: data.semester ? data.semester.semester : null,
        }))
        .put('/attestations', {
            onSuccess: () => {
                window.toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: `Subject '${attestationForm.subjectName}' updated`,
                    life: 3000,
                })
                reset();
                showDialog.value = false;
                isEdit.value = false;
                combinedData.value = combine(page.props.attestations);
            },
            onError: (error) => {
                for (const e in error) {
                    attestationForm.reset(e)
                }
            }
        })
}

const reset = () => {
    attestationForm.reset();
    taskCount.value = 1;
    successForm.value = false;

    for (const e in page.props.errors) {
        delete page.props.errors[e];
    }
}

const addTask = () => {
    attestationForm.attestations.push({
        id: isEdit ? attestationForm.attestations.length + 1 : taskCount.value++,
        title: null,
        description: null,
        task_id: null,
    })
}

const removeTask = () => {
    attestationForm.attestations.pop();
    taskCount.value--;
    delete page.props.errors['attestations.' + (taskCount.value - 1) + '.title'];
}

const confirm1 = (attestation) => {
    confirm.require({
        message: `Do you want to delete '${attestation.subject_name}'?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',

        accept: () => {
            axios.delete('/attestations', {
                data: {
                    attestation_id: attestation.id
                }
            })
                .then(response => {
                    for (let i = 0; i < combinedData.value.length; i++) {
                        if (response.data.attestation_id === combinedData.value[i].id) {
                            window.toast.add({
                                severity: 'success',
                                summary: 'Success',
                                detail: `Attestation '${combinedData.value[i].subject_name}' with ID ${combinedData.value[i].id} was deleted`,
                                life: 3000,
                            })
                            router.reload({
                                only: ['attestations'],
                            })
                            break;
                        }
                    }
                })
                .catch(error => {
                    window.toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: error.response.data.message,
                        life: 3000,
                    })
                })
        },
        reject: () => {
            //...
        }
    });
};

const handleEdit = (attestation) => {
    reset();
    isEdit.value = true;
    showDialog.value = true;
    attestationForm.subjectName = attestation.subject_name;
    attestationForm.subjectNumber = attestation.subject_number;
    for (let i = 0; i < page.props.semester.length; i++) {
        if (page.props.semester[i].semester === attestation.semester)
            attestationForm.semester = page.props.semester[i];

    }
    attestationForm.users = attestation.tasks.length === 0 ? null : [];
    for (let i = 0; i < attestation.tasks.length; i++) {
        for (let j = 0; j < page.props.users.length; j++) {
            if (attestation.tasks[i][0].user_id === page.props.users[j].id) {
                attestationForm.users.push(page.props.users[j]);
                break;
            }
        }
    }
    let count = 1
    for (const task of attestation.tasks[0]) {
        attestationForm.attestations.push({
            id: count++,
            task_id: task.task_id,
            title: task.title,
            description: task.description,
        })
        attestationForm.id = attestation.id;
    }
}

const handleAttestationInfo = (attestation, index) => {
    showAttestation.value = true;
    subject_name.value = combinedData.value[index].subject_name;
    tasks.value = combinedData.value[index].tasks;
    descriptions.value = [];

    let tmp = reduce_tasks(tasks.value, userData.value, headers.value);

    tasks.value = tmp.tasks;
    userData.value = tmp.userData;
    headers.value = tmp.headers;

    for (let i = 0; i < headers.value.length; i++) {
        descriptions.value.push(combinedData.value[index].tasks[0][i].description)
    }
}
</script>

<template>
    <Head title="Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        <span v-if="$page.props.auth.user.admin">Attestations Admin Panel</span>
                        <span v-else>My Attestations</span>
                    </h2>
                </div>
                <div class="ml-auto" v-if="$page.props.auth.user.admin">
                    <primary-button @click="handleDialogOpen">Create new Attestation</primary-button>
                </div>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="$page.props.auth.user.admin" v-for="(attestation, index) in combinedData"
                     :key="attestation.id"
                     class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-10 rounded-lg">
                    <div class="w-full bg-blue-500 h-3"/>
                    <Card class="break-words">
                        <template #title> {{ attestation.subject_name }} ({{ attestation.semester }})</template>
                        <template #subtitle>Subject Number: {{ attestation.subject_number }}</template>
                        <template #content>
                            <div class="grid grid-cols-2 max-md:grid-cols-1 justify-evenly gap-2">
                                <div class="w-1/2 w-full">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-user"/>
                                        <InputText style="font-weight: bold" class="w-full" disabled
                                                   :value="`Current Users: ${attestation.tasks.length}`"
                                                   placeholder="Search">
                                        </InputText>
                                    </span>
                                </div>
                                <div class="w-1/2 w-full">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-file"/>
                                        <InputText style="font-weight: bold" class="w-full" disabled
                                                   :value="`Tasks: ${attestation.tasks[0].length}`"
                                                   placeholder="Search"></InputText>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <Chart type="bar" :data="chartData[index]" :options="chartOptions"/>
                            </div>
                        </template>
                        <template #footer>
                            <div class="grid grid-cols-2 max-md:grid-cols-1">
                                <div>
                                    <Button @click="handleEdit(attestation)" icon="pi pi-file-edit" label="Edit"
                                            severity="success"/>
                                    <Button @click="confirm1(attestation)" icon="pi pi-trash" label="Delete"
                                            severity="danger" style="margin-left: 0.5em"/>
                                </div>
                                <div class="self-center md:ml-auto md:mr-5 max-md:mt-4">
                                    <Button @click="router.get(`/attestations/${attestation.id}`)"
                                            icon="pi pi-arrow-right"
                                            label="Make attestations" severity="info"/>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
                <div v-if="!$page.props.auth.user.admin" v-for="(attestation, index) in combinedData"
                     :key="attestation.id"
                     class="mb-10 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="w-full bg-blue-500 h-3"/>
                    <Dialog v-model:visible="showAttestation" modal :header="subject_name" :style="{ width: '90vw' }">
                        <TabView :scrollable="true">
                            <TabPanel>
                                <template #header>
                                    <i class="pi pi-calendar mr-2"></i>
                                    <span>Attestation</span>
                                </template>
                                <DataTable showGridlines stripedRows :value="userData">
                                    <Column field="Name" header="Name"></Column>
                                    <Column v-for="header in headers" :field="header" :key="header">
                                        <template #header="{ index }">
                                            <div class="mx-auto">
                                                <div>{{ header }}</div>
                                            </div>
                                        </template>
                                        <template #body="{ index, field, data }">
                                            <div class="flex justify-center items-center h-full">
                                                <Checkbox disabled v-model="data[field]" :binary="true"/>
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </TabPanel>
                            <TabPanel v-for="(header, index1) in headers" :key="header">
                                <template #header>
                                    <i class="pi pi-file-edit mr-2"></i>
                                    <span class="font-medium" style="white-space: nowrap">{{ header }}</span>
                                </template>
                                <Editor v-if="descriptions[index1]" class="h-full w-full" readonly
                                        v-model="descriptions[index1]">
                                    <template #toolbar>
                                        <span></span>
                                    </template>
                                </Editor>
                                <span v-else>
                                    <em>No Description available.</em>
                                </span>
                            </TabPanel>
                        </TabView>
                    </Dialog>
                    <Card class="rounded-lg">
                        <template #title> {{ attestation.subject_name }} ({{ attestation.semester }})</template>
                        <template #subtitle>Subject Number: {{ attestation.subject_number }}</template>
                        <template #content>
                            <div class="flex flex-wrap justify-evenly gap-2">
                                <div class="w-1/2 max-md:w-full">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-file"/>
                                        <InputText class="w-full" disabled
                                                   :value="`Tasks: ${attestation.tasks[0].length}`"
                                                   placeholder="Search"></InputText>
                                    </span>
                                </div>
                            </div>
                        </template>
                        <template #footer>
                            <div class="grid grid-cols-2 max-md:grid-cols-1">
                                <div>
                                    <Button @click="handleAttestationInfo(attestation, index)" icon="pi pi-info-circle"
                                            label="Info" severity="success"/>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <template
            v-if="attestations.length === 0 || ($page.props.auth.user.admin && Array.isArray(combinedData) && combinedData.length === 0)">
            <div class="text-gray-700 text-center">
                <div style="font-size: 10rem" class="pi pi-book"></div>
            </div>
            <div class="text-gray-500 text-center mt-4">
                No Attestations assigned to you
            </div>
        </template>

        <span v-if="$page.props.auth.user.admin">
            <ConfirmDialog ref="confirmDialog"
                           class="bg-white p-4 custom-confirm-dialog rounded-md gap-8 break-all"></ConfirmDialog>

            <Dialog v-model:visible="showDialog" modal :header="isEdit ? 'Edit' : 'Create new Attestation'"
                    :style="{ width: '90vw' }">
                <form @submit.prevent="handleForm">
                    <span class="p-float-label mt-5">
                        <MultiSelect :disabled="attestationForm.processing" :loading="!$props.users"
                                     v-model="attestationForm.users" :options="userWithMatriculationNumber" filter
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
                        {{error}}
                    </error-message>
                </span>
                    <div class="grid xl:grid-cols-2 xl:gap-4 mt-4">
                        <div class="my-4">
                            <span class="p-input-icon-right w-full p-float-label">
                                <i class="pi pi-hashtag"/>
                                <input-number :disabled="attestationForm.processing"
                                              v-model="attestationForm.subjectNumber" :useGrouping="false"
                                              class="w-full"></input-number>
                                <label for="subject_number">Subject Number</label>
                            </span>
                            <error-message :show="errors.subjectNumber">
                                {{ errors.subjectNumber }}
                            </error-message>
                        </div>
                        <div class="my-4">
                            <span class="p-input-icon-right w-full p-float-label">
                                <i class="pi pi-book"/>
                                <input-text :disabled="attestationForm.processing" v-model="attestationForm.subjectName"
                                            class="w-full"></input-text>
                                <label for="subject_name">Subject Name</label>
                            </span>
                            <error-message :show="errors.subjectName">
                                {{ errors.subjectName }}
                            </error-message>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="p-float-label">
                            <Dropdown :disabled="attestationForm.processing" v-model="attestationForm.semester"
                                      :options="semester" optionLabel="semester"
                                      class="max-md:w-[16rem] w-80"/>
                            <label for="semester">Semester</label>
                        </span>
                        <error-message :show="errors.semester">
                            {{ errors.semester }}
                        </error-message>
                    </div>
                    <div class="mt-4">
                        <div v-for="task in attestationForm.attestations" :key="task.id" class="my-4 w-full">
                            <div class="mb-1 font-bold">
                                {{ task.id }}. Attestation
                            </div>
                            <div>
                                <input-text :disabled="attestationForm.processing"
                                            v-model="attestationForm.attestations[task.id - 1].title" class="w-full"
                                            placeholder="Title"></input-text>
                                <error-message
                                    :show="Object.keys(errors).some(key => key.startsWith('attestations.' + (task.id - 1) + '.title'))">
                                    {{ errors['attestations.' + (task.id - 1) + '.title'] }}
                                </error-message>
                            </div>
                            <div class="mt-2">
                                <Editor v-model="attestationForm.attestations[task.id - 1].description"
                                        placeholder="Description or further instructions" editorStyle="height:20rem"
                                        class="w-full">
                                    <template v-slot:toolbar>
                                        <span class="ql-formats">
                                            <select class="ql-header">
                                                <option value="1">Heading</option>
                                                <option value="2">Subheading</option>
                                                <option value="3">Normal</option>
                                            </select>
                                        </span>
                                        <span class="ql-formats">
                                            <select class="ql-font">
                                                <option value="sans-serif">Sans Serif</option>
                                                <option value="serif">Serif</option>
                                                <option value="monospace">Monospace</option>
                                            </select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-bold"></button>
                                            <button class="ql-italic"></button>
                                            <button class="ql-underline"></button>
                                        </span>
                                        <span class="ql-formats">
                                                <select class="ql-color">
                                                    <template v-for="color in colors">
                                                        <option :value="color.rgb" :label="color.label"></option>
                                                    </template>
                                                </select>
                                                <select class="ql-background">
                                                    <template v-for="color in colors">
                                                        <option :value="color.rgb" :label="color.label"></option>
                                                    </template>
                                                </select>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-list" value="ordered"></button>
                                                <button class="ql-list" value="bullet"></button>
                                                <select class="ql-align">
                                                    <option value=""></option>
                                                    <option value="center"></option>
                                                    <option value="right"></option>
                                                    <option value="justify"></option>
                                                </select>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-link" value="bullet"></button>
                                                <button class="ql-code-block"></button>
                                            </span>
                                    </template>
                                </Editor>
                                <error-message
                                    :show="Object.keys(errors).some(key => key.startsWith('attestations.' + (task.id - 1) + '.description'))">
                                    {{ errors['attestations.' + (task.id - 1) + '.description'] }}
                                </error-message>
                            </div>
                        </div>
                    </div>
                    <Button @click="addTask" icon="pi pi-plus" aria-label="Filter"/>
                    <span v-if="!isEdit && attestationForm.attestations.length > 0" class="ml-3"><Button
                        @click="removeTask"
                        icon="pi pi-trash" severity="danger" aria-label="Filter"/></span>
                    <span v-else-if="isEdit && attestationForm.attestations.length > 1" class="ml-3"><Button
                        @click="removeTask" icon="pi pi-trash" severity="danger" aria-label="Filter"/></span>
                    <error-message :show="errors.attestations">
                        {{ errors.attestations }}
                    </error-message>
                    <div class="my-4 grid grid-cols-2">
                        <div class="justify-center">
                            <CustomProgressSpinner :processing="attestationForm.processing"></CustomProgressSpinner>
                        </div>
                        <div class="flex justify-end" style="height: 3rem">
                            <primary-button class="mr-5 disabled:cursor-not-allowed"
                                            :disabled="attestationForm.processing || (!attestationForm.users || !attestationForm.subjectName || !attestationForm.subjectNumber || !attestationForm.semester || attestationForm.attestations.length === 0)">{{
                                    isEdit ? "Save Changes" :
                                        "Create new subject"
                                }}</primary-button>
                            <secondary-button @click="handleDialogClose">Cancel</secondary-button>
                            <span v-if="!isEdit" class="ml-10 max-md:hidden">
                                <Button severity="danger" aria-label="Cancel" @click="reset">Reset</Button>
                            </span>
                        </div>
                    </div>
                </form>
            </Dialog>
        </span>
    </AuthenticatedLayout>
</template>

<style>
.p-card {
    border-top-left-radius: revert;
    border-top-right-radius: revert;
}
</style>
