<script setup>
import {Head, useForm, usePage, router} from '@inertiajs/vue3';
import {computed, onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';
import ButtonBar from '@/Components/ButtonBar.vue';

import {useConfirm} from 'primevue/useconfirm';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Editor from 'primevue/editor';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import SelectButton from 'primevue/selectbutton';

import combine from '@/CombinedData.js';
import reduce_tasks from '@/ReduceTasks.js';


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

const showAttestationDialog = ref(false);
const showAttestationInfoDialog = ref(false);
const isEdit = ref(false);
const taskCount = ref(1);
const combinedData = ref(null);
const subject_name = ref("");
const tasks = ref([]);
const userData = ref([]);
const userWithMatriculationNumber = ref([]);
const headers = ref(null);
const descriptions = ref([]);
const chartData = ref([]);
const chart = ref("Polar");
const chartSelect = ref(["Pie", "Polar", "Bar"])

const attestationForm = useForm({
    id: null,
    users: [],
    subjectNumber: null,
    subjectName: null,
    acronym: null,
    semester: null,
    attestations: [],
})

const userFileForm = useForm({
    id: null,
    userfile: null,
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
    createNameWithMatNumber();
    chartData.value = [];
    setupChart();
})

onBeforeUpdate(() => {
    combinedData.value = combine(page.props.attestations);
    createNameWithMatNumber();
    chartData.value = [];
    setupChart();
})

const noAttestations = computed(() => {
    return page.props.attestations.length === 0 || (page.props.auth.user.admin && Array.isArray(combinedData.value) && combinedData.value.length === 0)
})

const disableFormSend = computed(() => {
    return attestationForm.processing || (!attestationForm.subjectName || !attestationForm.subjectNumber || !attestationForm.acronym || !attestationForm.semester || attestationForm.attestations.length === 0)
})

const buttonLabel = computed(() => {
    return isEdit.value ? "Save Changes" : "Create new subject"

})

const createNameWithMatNumber = () => {
    userWithMatriculationNumber.value = page.props.users;
    userWithMatriculationNumber.value = userWithMatriculationNumber.value.slice().sort((a, b) => {
        const surnameA = a.name.split(' ').slice(-1)[0];
        const surnameB = b.name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });
    userWithMatriculationNumber.value.map(user => {
        user.name = `${user.name} (${user.matriculation_number})`;
    })
}

const setupChart = () => {
    for (let i = 0; i < combinedData.value.length; i++) {
        chartData.value.push({
            labels: [],
            datasets: [
                {
                    label: 'Checked',
                    data: [],
                    backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
                    borderColor: ['rgb(255, 159, 64)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
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
    resetForm();
    showAttestationDialog.value = true;
    isEdit.value = false;
}

const handleDialogClose = () => {
    resetForm();
    showAttestationDialog.value = false;
    isEdit.value = false;
}

const handleFormSend = () => {
    if (!isEdit.value) {
        attestationForm
            .transform((data) => ({
                ...data,
                semester: data.semester ? data.semester.semester : null,
            }))
            .post('/attestations', {
                onSuccess: () => {
                    window.toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: `Subject '${attestationForm.subjectName}' created`,
                        life: 3000,
                    })
                    resetForm();
                    showAttestationDialog.value = false;
                    combinedData.value = combine(page.props.attestations);
                },
                onError: (error) => {
                    for (const e in error) {
                        attestationForm.reset(e)

                        if (e.includes('users.')) {
                            attestationForm.reset('users');
                        }
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
            preserveScroll: true,
            onSuccess: () => {
                window.toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: `Subject '${attestationForm.subjectName}' updated`,
                    life: 3000,
                })
                resetForm();
                showAttestationDialog.value = false;
                isEdit.value = false;
                combinedData.value = combine(page.props.attestations);
            },
            onError: (error) => {
                if (error.id) {
                    window.toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: error.id,
                        life: 8000,
                    })
                }
                for (const e in error) {
                    attestationForm.reset(e)
                }
            }
        })
}

const resetForm = () => {
    attestationForm.reset();
    taskCount.value = 1;

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

const confirmAttestationDeletion = (attestation) => {
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
                                detail: `Subject '${combinedData.value[i].subject_name}' with ID ${combinedData.value[i].id} deleted`,
                                life: 3000,
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
                        life: 8000,
                    })
                })
                .then(() => {
                    router.reload({
                        only: ['attestations', 'users'],
                    })
                })
        },
        reject: () => {
            //...
        }
    });
};

const handleAttestationEdit = (attestation) => {
    resetForm();
    isEdit.value = true;
    showAttestationDialog.value = true;
    attestationForm.subjectName = attestation.subject_name;
    attestationForm.subjectNumber = attestation.subject_number;
    attestationForm.acronym = attestation.acronym;
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
        if (task.title === import.meta.env.VITE_FINAL_ATTESTATION_NAME) continue;
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
    showAttestationInfoDialog.value = true;
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

const handleUserFileUpload = (attestation) => {
    userFileForm.id = attestation.id;
    userFileForm.post('/attestations/users', {
        preserveScroll: true,
        onSuccess: () => {
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `Users added to '${attestation.subject_name}'`,
                life: 3000,
            })
        },
        onError: (errors) => {
            for (const error in errors) {
                window.toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: errors[error],
                    life: 8000,
                })
            }
        },
        onFinish: () => userFileForm.reset()
    })
}

const combinedDataSorted = computed(() => {
    return id => {
        if (combinedData.value) {
            return combinedData.value.filter(item => item.semester_id === id);
        }
    }
})
</script>

<template>
    <Head title="Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">
                        <span v-if="$page.props.auth.user.admin">Attestations Admin Panel</span>
                        <span v-else>My Attestations</span>
                    </h2>
                </div>
                <div class="ml-auto" v-if="$page.props.auth.user.admin">
                    <primary-button @click="handleDialogOpen">Create new Subject</primary-button>
                </div>
            </div>
        </template>
        <div class="py-12">
            <div v-if="!noAttestations" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Accordion :activeIndex="0">
                    <AccordionTab v-for="(s, index1) in semester">
                        <template #header>
                            <i class="pi pi-calendar mr-2"></i>
                            <span>{{s.semester}}</span>
                        </template>


                        <Accordion class="shadow-xl" v-if="$page.props.auth.user.admin">
                            <AccordionTab v-for="(attestation, index) in combinedDataSorted(s.id)" :key="`${s.id}_${index1}_${attestation.id}_${index}`" :header="`${attestation.subject_name} (${attestation.subject_number})`">
                                <div>
                                    <div class="shadow-xl">
                                        <Card class="break-words border">
                                            <template #title>
                                                <div>
                                                    {{ attestation.subject_name }} ({{ attestation.semester }})
                                                </div>
                                            </template>
                                            <template #subtitle>Subject Number: {{ attestation.subject_number }}</template>
                                            <template #content>
                                                <div class="grid grid-cols-2 justify-evenly gap-2 max-md:grid-cols-1">
                                                    <div>
                                                        <span class="p-input-icon-left w-full">
                                                            <i class="pi pi-user"/>
                                                            <InputText class="w-full custom-input-text" disabled
                                                                       placeholder="Search"
                                                                       :value="`Current Users: ${attestation.tasks[0][0].user_id ? attestation.tasks.length : 0}`">
                                                            </InputText>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="p-input-icon-left w-full">
                                                            <i class="pi pi-file"/>
                                                            <InputText class="w-full custom-input-text" disabled
                                                                       placeholder="Search"
                                                                       :value="`Tasks: ${attestation.tasks[0].length}`">
                                                            </InputText>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex">
                                                    <SelectButton class="mx-auto my-2" v-model="chart" :options="chartSelect"/>
                                                </div>
                                                <div>
                                                    <Chart class="md:w-1/2 mx-auto" v-if="chart === 'Pie'" type="pie" :data="chartData[attestation.index]"/>
                                                    <Chart class="md:w-1/2 mx-auto" v-else-if="chart === 'Polar'" type="polarArea" :data="chartData[attestation.index]"/>
                                                    <Chart v-else-if="chart === 'Bar'" type="bar" :data="chartData[attestation.index]"/>
                                                </div>
                                            </template>
                                            <template #footer>
                                                <div class="grid grid-cols-2 max-md:grid-cols-1">
                                                    <div class="flex flex-wrap gap-2">
                                                        <Button label="Edit"
                                                                severity="success"
                                                                :disabled="userFileForm.processing"
                                                                @click="handleAttestationEdit(attestation)" icon="pi pi-file-edit"/>
                                                        <Button label="Delete"
                                                                severity="danger"
                                                                :disabled="userFileForm.processing"
                                                                @click="confirmAttestationDeletion(attestation)" icon="pi pi-trash"/>
                                                        <FileUpload
                                                            accept="text/csv"
                                                            customUpload chooseLabel="Upload"
                                                            v-tooltip.right="'Provide a CSV file containing the matriculation numbers of the users for simultaneous inclusion to this subject'"
                                                            :disabled="userFileForm.processing" mode="basic" name="userfile[]"
                                                            :maxFileSize="1e7"
                                                            :auto="false"
                                                            @uploader="handleUserFileUpload(attestation)"
                                                            @input="userFileForm.userfile = $event.target.files[0];" :multiple="false"/>
                                                    </div>
                                                    <div class="self-center md:ml-auto md:mr-5 max-md:mt-4">
                                                        <Button icon="pi pi-arrow-right"
                                                                label="Make attestations" severity="info"
                                                                :disabled="userFileForm.processing"
                                                                @click="router.get(`/attestations/${attestation.id}`,{},{preserveScroll:true})"/>
                                                    </div>
                                                </div>
                                            </template>
                                        </Card>
                                    </div>
                                </div>
                            </AccordionTab>
                        </Accordion>


                        <Accordion class="shadow-xl" v-if="!$page.props.auth.user.admin">
                            <AccordionTab v-for="(attestation, index) in combinedDataSorted(s.id)" :key="`${s.id}_${index1}_${attestation.id}_${index}`" :header="`${attestation.subject_name} (${attestation.subject_number})`">
                                <div>
                                    <div class="shadow-xl mb-4">
                                        <Card class="rounded-lg border">
                                            <template #title> {{ attestation.subject_name }} ({{ attestation.semester }})</template>
                                            <template #subtitle>Subject Number: {{ attestation.subject_number }}</template>
                                            <template #content>
                                                <div class="flex flex-wrap justify-evenly gap-2">
                                                    <div class="w-1/2 max-md:w-full">
                                                        <span class="p-input-icon-left w-full">
                                                            <i class="pi pi-file"/>
                                                            <InputText class="w-full" disabled
                                                                       placeholder="Search"
                                                                       :value="`Tasks: ${attestation.tasks[0].length}`"/>
                                                        </span>
                                                    </div>
                                                </div>
                                                <Chart type="bar" :data="chartData[attestation.index]"/>
                                            </template>
                                            <template #footer>
                                                <div class="grid grid-cols-2 max-md:grid-cols-1">
                                                    <div>
                                                        <Button icon="pi pi-info-circle"
                                                                label="Info" severity="success"
                                                                @click="handleAttestationInfo(attestation, attestation.index)"/>
                                                    </div>
                                                </div>
                                            </template>
                                        </Card>
                                    </div>
                                </div>
                            </AccordionTab>
                        </Accordion>
                    </AccordionTab>
                </Accordion>
            </div>
        </div>

        <template
            v-if="noAttestations">
            <div class="text-gray-700 text-center">
                <div class="pi pi-book custom-icon"></div>
            </div>
            <div class="text-gray-500 text-center mt-4">
                No Attestations assigned to you
            </div>
        </template>

        <span v-if="$page.props.auth.user.admin">
            <ConfirmDialog class="bg-white p-4 custom-confirm-dialog rounded-md gap-8 break-words" ref="confirmDialog"/>

            <Dialog v-model:visible="showAttestationDialog" modal :header="isEdit ? 'Edit' : 'Create new Subject'"
                    :style="{ width: '90vw' }">
                <form @submit.prevent="handleFormSend">
                    <span class="p-float-label mt-5">
                        <MultiSelect class="w-full md:w-20rem" filter :disabled="attestationForm.processing"
                                     :loading="!$props.users"
                                     v-model="attestationForm.users" :options="userWithMatriculationNumber"
                                     optionLabel="name" :maxSelectedLabels="3"
                                     :virtualScrollerOptions="{ itemSize: 44 }"/>
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
                    <Message v-if="!isEdit" :closable="false">
                        After creating, you'll have the opportunity to upload a CSV file containing the matriculation numbers of users you wish to add to this subject. This way, you won't need to select users at this moment.
                    </Message>
                    <div class="grid mt-4 xl:grid-cols-3 xl:gap-4">
                        <div class="my-4">
                            <span class="p-input-icon-right w-full p-float-label">
                                <i class="pi pi-hashtag"/>
                                <input-text class="w-full" :disabled="attestationForm.processing"
                                            v-model="attestationForm.subjectNumber" :useGrouping="false"/>
                                <label for="subject_number">Subject Number</label>
                            </span>
                            <error-message :show="errors.subjectNumber">
                                {{ errors.subjectNumber }}
                            </error-message>
                        </div>
                        <div class="my-4">
                            <span class="p-input-icon-right w-full p-float-label">
                                <i class="pi pi-book"/>
                                <input-text class="w-full" :disabled="attestationForm.processing"
                                            v-model="attestationForm.subjectName"/>
                                <label for="subject_name">Subject Name</label>
                            </span>
                            <error-message :show="errors.subjectName">
                                {{ errors.subjectName }}
                            </error-message>
                        </div>
                        <div class="my-4">
                            <span class="p-input-icon-right w-full p-float-label">
                                <i class="pi pi-qrcode"/>
                                <input-text class="w-full" :disabled="attestationForm.processing"
                                            v-model="attestationForm.acronym" :useGrouping="false"/>
                                <label for="subject_number">Acronym</label>
                            </span>
                            <error-message :show="errors.acronym">
                                {{ errors.acronym }}
                            </error-message>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="p-float-label">
                            <Dropdown class="max-md:w-[16rem] w-80" :disabled="attestationForm.processing"
                                      v-model="attestationForm.semester"
                                      :options="semester" optionLabel="semester"/>
                            <label for="semester">Semester</label>
                        </span>
                        <error-message :show="errors.semester">
                            {{ errors.semester }}
                        </error-message>
                    </div>
                    <div class="mt-4">
                        <div class="my-4 w-full" v-for="task in attestationForm.attestations" :key="task.id">
                            <div class="mb-1 font-bold">
                                {{ task.id }}. Attestation
                            </div>
                            <div>
                                <input-text class="w-full" :disabled="attestationForm.processing"
                                            v-model="attestationForm.attestations[task.id - 1].title"
                                            placeholder="Title"></input-text>
                                <error-message
                                    :show="Object.keys(errors).some(key => key.startsWith('attestations.' + (task.id - 1) + '.title'))">
                                    {{ errors['attestations.' + (task.id - 1) + '.title'] }}
                                </error-message>
                            </div>
                            <div class="mt-2">
                                <Editor class="w-full" v-model="attestationForm.attestations[task.id - 1].description"
                                        placeholder="Description or further instructions" editorStyle="height:20rem">
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
                    <Button icon="pi pi-plus" label="Add task" aria-label="Filter" @click="addTask"/>
                    <span class="ml-3" v-if="!isEdit && attestationForm.attestations.length > 0"><Button
                        icon="pi pi-trash" severity="danger" aria-label="Filter" @click="removeTask"/></span>
                    <span class="ml-3" v-else-if="isEdit && attestationForm.attestations.length > 1"><Button
                        icon="pi pi-trash" severity="danger" aria-label="Filter" @click="removeTask"/></span>
                    <error-message :show="errors.attestations">
                        {{ errors.attestations }}
                    </error-message>
                    <ButtonBar @handle-close="handleDialogClose" :processing="attestationForm.processing" :disable_primary="disableFormSend">
                        <template #primary>
                            {{buttonLabel}}
                        </template>
                        <template #additional>
                            <span v-if="!isEdit" class="ml-10 max-md:hidden">
                                <Button severity="danger" aria-label="Cancel" @click="resetForm">Reset</Button>
                            </span>
                        </template>
                    </ButtonBar>
                </form>
            </Dialog>
        </span>
    </AuthenticatedLayout>

    <Dialog v-model:visible="showAttestationInfoDialog" modal :header="subject_name"
            :style="{ width: '90vw' }">
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
                    <span class="font-medium whitespace-nowrap">{{ header }}</span>
                </template>
                <Editor class="h-full w-full" readonly
                        v-if="descriptions[index1]"
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
</template>

<style>
.p-card {
    border-top-left-radius: revert;
    border-top-right-radius: revert;
}

.custom-input-text {
    font-weight: bold
}

.custom-icon {
    font-size: 10rem
}
</style>
