<script setup>
import {Head, useForm, usePage, router} from '@inertiajs/vue3';
import {computed, onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';
import ButtonBar from '@/Components/ButtonBar.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';

import {useConfirm} from 'primevue/useconfirm';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Editor from 'primevue/editor';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import OverlayPanel from 'primevue/overlaypanel';
import InlineMessage from 'primevue/inlinemessage';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

import combine from '@/CombinedData.js';


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
    },
    additional_attestations: {
        type: Array
    }
})


const page = usePage();
const confirm = useConfirm();

const showAttestationDialog = ref(false);
const isEdit = ref(false);
const taskCount = ref(1);
const combinedData = ref(null);
const subject_name = ref("");
const tasks = ref([]);
const userWithMatriculationNumber = ref([]);
const headers = ref(null);
const selectedSubject = ref(null);
/*
const chartData = ref([]);
const chart = ref("Polar");
const chartSelect = ref(["Pie", "Polar", "Bar"])
*/
const op = ref();
const selectedSemester = ref(null);

const filters = ref({
    'global': {value: null, matchMode: 'contains'},
});

const attestationForm = useForm({
    id: null,
    users: [],
    subjectNumber: null,
    subjectName: null,
    acronym: null,
    semester: null,
    attestations: [],
})

const includeUserForm = useForm({
    attestation_id: null,
    users: [],
})

const userFileForm = useForm({
    id: null,
    userfile: null,
})

/*
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
*/

onMounted(() => {
    combinedData.value = combine(page.props.attestations);
    createNameWithMatNumber();
    //chartData.value = [];
    //setupChart();

    if (localStorage.getItem("attestation_semester")) {
        for (const semester of page.props.semester) {
            if (semester.id.toString() === localStorage.getItem("attestation_semester"))
                selectedSemester.value = semester;
        }
    }
})

onBeforeUpdate(() => {
    combinedData.value = combine(page.props.attestations);
    createNameWithMatNumber();
    //chartData.value = [];
    //setupChart();
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

const checkCreateSubjectPrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_create_subject' && p.checked) {
            return true;
        }
    }
    return false;
})

const checkEditSubjectPrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_edit_subject' && p.checked) {
            return true;
        }
    }
    return false;
})

const checkDeleteSubjectPrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_delete_subject' && p.checked) {
            return true;
        }
    }
    return false;
})

const checkCanAccessAttestationPage = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_access_attestation_page' && p.checked) {
            return true;
        }
    }
    return false;
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

/*
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
*/

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
    selectedSubject.value = attestation;
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

const handleUserFileUpload = (attestation) => {
    userFileForm.id = attestation.id;
    userFileForm.post('/attestations/users/upload', {
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
        onFinish: () => {
            userFileForm.reset();
            showAttestationDialog.value = false;
        }
    })
}

const combinedDataSorted = () => {
    if (combinedData.value) {
        return combinedData.value.filter(item => item.semester_id === selectedSemester.value.id);
    }
}

const toggle = (event, attestation_id) => {
    includeUserForm.users = [];
    op.value.toggle(event);
    includeUserForm.attestation_id = attestation_id;

    page.props.users.forEach(user => {
        page.props.additional_attestations.forEach(item => {
            if (item.user_id === user.id && item.attestation_id === attestation_id)
                includeUserForm.users.push(user);
        })
    })
}

const handleIncludeUserToAdditionalAttestation = () => {
    includeUserForm.post('/attestations/users/include', {
        preserveScroll: true,
        onSuccess: () => {
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Users included to this subject.',
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
        onFinish: () => {
            includeUserForm.users = [];
            toggle();
        }
    })
}

const handleSemesterChange = () => {
    localStorage.setItem("attestation_semester",selectedSemester.value.id);
}
</script>

<template>
    <Head title="Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">
                        <span>Attestations Panel</span>
                    </h2>
                </div>
                <div class="ml-auto" v-if="$page.props.auth.user.admin || checkCreateSubjectPrivilege">
                    <primary-button @click="handleDialogOpen">Create new Subject</primary-button>
                </div>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <DataTable :value="combinedDataSorted()"  stripedRows ref="dataTable"
                           v-model:filters="filters" paginator :rows="10">
                    <template #header>
                        <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="flex justify-content-end">
                                        <span class="p-input-icon-left">
                                            <i class="pi pi-search"/>
                                            <InputText v-model="filters['global'].value" placeholder="Search"/>
                                        </span>
                            </div>
                            <div class="ml-auto">
                                <Dropdown class="max-md:w-[16rem] w-80 ml-auto mb-4" placeholder="Select semester"
                                          v-model="selectedSemester" @change="handleSemesterChange"
                                          :options="semester" optionLabel="semester"/>
                            </div>
                        </div>
                    </template>
                    <Column class="font-semibold" field="subject_number" header="Subject Number">
                    </Column>
                    <Column class="font-semibold" field="subject_name" header="Subject Name"></Column>
                    <Column class="font-semibold" field="category" header="Current Users">
                        <template #body="{data}">
                            {{data.tasks[0][0].user_id ? data.tasks.length : 0}}
                        </template>
                    </Column>
                    <Column field="Name" header="Make">
                        <template #body="{ index, field, data }">
                            <Button icon="pi pi-arrow-right"
                                    severity="info"
                                    v-if="page.props.auth.user.admin || checkCanAccessAttestationPage"
                                    :disabled="userFileForm.processing"
                                    @click="router.get(`/attestations/${data.id}`,{},{preserveScroll:true})"/>
                        </template>
                    </Column>
                    <Column field="quantity" header="Options">
                        <template #body="{ index, field, data}">
                            <div class="flex gap-2">
                                <Button severity="success"
                                        v-if="page.props.auth.user.admin || checkEditSubjectPrivilege"
                                        :disabled="userFileForm.processing"
                                        @click="handleAttestationEdit(data)" icon="pi pi-file-edit"/>
                                <Button severity="danger"
                                        v-if="page.props.auth.user.admin || checkDeleteSubjectPrivilege"
                                        :disabled="userFileForm.processing"
                                        @click="confirmAttestationDeletion(data)" icon="pi pi-trash"/>
                                <Button v-if="$page.props.auth.user.admin || data.creator_id === $page.props.auth.user.id" icon="pi pi-user" aria-label="Submit" @click="toggle($event,data.id)"/>
                                <!--
                                <FileUpload
                                    accept="text/csv"
                                    customUpload chooseLabel=""
                                    v-tooltip.right="'Provide a CSV file containing the matriculation numbers of the users for simultaneous inclusion to this subject'"
                                    v-if="page.props.auth.user.admin || checkEditSubjectPrivilege"
                                    :disabled="userFileForm.processing" mode="basic" name="userfile[]"
                                    :maxFileSize="1e7"
                                    :auto="false"
                                    @uploader="handleUserFileUpload(data)"
                                    @input="userFileForm.userfile = $event.target.files[0];" :multiple="false"/>
                                    -->
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

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
                    <div class="mt-2 flex flex-wrap gap-2">
                        <FileUpload
                            accept="text/csv"
                            customUpload chooseLabel="CSV"
                            v-if="page.props.auth.user.admin || checkEditSubjectPrivilege"
                            :disabled="userFileForm.processing" mode="basic" name="userfile[]"
                            :maxFileSize="1e7"
                            :auto="false"
                            @uploader="handleUserFileUpload(selectedSubject)"
                            @input="userFileForm.userfile = $event.target.files[0];" :multiple="false"/>
                        <InlineMessage severity="info">Provide a CSV file containing the matriculation numbers of the users for simultaneous inclusion to this subject</InlineMessage>
                    </div>
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
        <OverlayPanel ref="op">
            <div class="flex flex-wrap gap-3">
                <InlineMessage class="w-full" severity="warn">Users included here will have access to this subject</InlineMessage>
                <MultiSelect class="w-full" filter :disabled="includeUserForm.processing"
                             :loading="!$props.users" placeholder="Users"
                             v-model="includeUserForm.users" :options="userWithMatriculationNumber"
                             optionLabel="name" :maxSelectedLabels="0"
                             :virtualScrollerOptions="{ itemSize: 44 }"/>
                <Button icon="pi pi-check" label="Save" :disabled="includeUserForm.processing" @click="handleIncludeUserToAdditionalAttestation"></Button>
                <div class="justify-center">
                    <CustomProgressSpinner :processing="includeUserForm.processing"></CustomProgressSpinner>
                </div>
            </div>
        </OverlayPanel>
    </AuthenticatedLayout>
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
