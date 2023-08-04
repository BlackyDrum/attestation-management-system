<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import {onBeforeUpdate, onMounted, ref} from "vue";

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import Button from "primevue/button";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import {FilterService} from 'primevue/api';

import combine from "@/CombinedData.js";

defineProps({
    attestations: {
        type: Object
    },
    id: {
        type: Number
    }
})


const page = usePage();

const combinedData = ref(null);
const subject_name = ref("");
const tasks = ref([]);
const userData = ref(null);
const headers = ref(null);
const userWithMatriculationNumber = ref([]);
const formData = ref([]);
const checkedCount = ref({});
const dt = ref();
const FILTER = ref('FILTER');
const filters = ref({
    'Name': {value: null, matchMode: 'contains'},
});

onMounted(() => {
    updateData();
});

onBeforeUpdate(() => {
    updateData();
    formData.value = [];
})

function updateData() {
    combinedData.value = combine(page.props.attestations);
    subject_name.value = combinedData.value[0].subject_name;
    tasks.value = combinedData.value[0].tasks;

    const uniqueTitles = Array.from(new Set(tasks.value.flat().map((item) => item.title)));

    const usersData = {};

    tasks.value.flat().forEach((item) => {
        const { name, title, task_id, checked, user_id, editor_name, updated_at, matriculation_number } = item;
        const key = `${name}-${user_id}`;

        if (!usersData[key]) {
            usersData[key] = { Name: name, user_id };
            uniqueTitles.forEach((t) => {
                usersData[key][t] = false;
            });
        }

        usersData[key][title] = checked;
        usersData[key]['matriculation_number'] = matriculation_number;
        usersData[key][`task_id_${title}`] = task_id;
        usersData[key][`editor_name_${title}`] = editor_name;
        usersData[key][`updated_at_${title}`] = updated_at;
    });

    userData.value = Object.values(usersData);
    userData.value = userData.value.slice().sort((a, b) => {
        const surnameA = a.Name.split(' ').slice(-1)[0];
        const surnameB = b.Name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });

    headers.value = Object.keys(userData.value[0]).filter((key) => key !== 'Name' && key !== 'matriculation_number' && key !== 'user_id' && key !== 'editor_id' && !key.startsWith('updated_at') && !key.startsWith('editor_name') && !key.startsWith('task_id'));

    for (const header of headers.value) {
        checkedCount.value[header] = 0;
    }

    let sortable = [];
    for (const header of headers.value) {
        sortable.push([header, userData.value[0][`task_id_${header}`]]);
    }
    sortable.sort((a,b) => {
        return a[1] - b[1];
    });
    headers.value = sortable.flat().filter(item => headers.value.includes(item));

    for (const user of userData.value) {
        for (const header of headers.value) {
            if (user[header])
                checkedCount.value[header]++;
        }
    }

    userWithMatriculationNumber.value = userData.value;
    userWithMatriculationNumber.value.map(user => {
        user.Name = `${user.Name} (${user.matriculation_number})`;
    })

}

const extractData = (data, index) => {
    const keys = (Object.keys(data).filter(key => key.startsWith('task_id'))).map(key => key.replace('task_id_', ''));

    formData.value = formData.value.filter(obj => obj.user_id !== data.user_id);

    for (const key of keys) {
        formData.value.push({
            user_id: data.user_id,
            checked: Boolean(data[key]),
            task_id: data[`task_id_${key}`],
        })
    }
}

const handleFormSend = () => {
    axios.patch('/attestations', {
        tasks: formData.value
    })
        .then(response => {
            console.log(response.data)
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: "Attestation updated",
                life: 3000,
            })
            router.reload()
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

const exportCSV = () => {
    dt.value.exportCSV();
};

FilterService.register(FILTER.value, (value, filter) => {

    if (filter === undefined || filter === null || filter.trim() === '') return true;

    if (value === undefined || value === null) return false;

    return value.toString() === filter.toString();
});
</script>

<template>
    <Head title="Make Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight break-words">Make
                Attestation for
                '{{ subject_name }}'</h2>
        </template>
        <div class="mt-5 ml-5 text-gray-400 ">
            <Button @click="router.get('/attestations')" icon="pi pi-arrow-left" class="h-10" severity="secondary"
                    label="Back"></Button>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div>
                        <DataTable showGridlines stripedRows ref="dt"
                                   :exportFilename="(subject_name + '_' + Date.now()).replaceAll(' ', '_')"
                                   v-model:filters="filters" filterDisplay="row" :value="userData" :paginator="true"
                                   :rows="10">
                            <template #header>
                                <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div>
                                        <Button icon="pi pi-external-link" label="Export CSV"
                                                @click="exportCSV($event)"/>
                                    </div>
                                    <div class="md:ml-auto md:mr-4">
                                        <Button @click="handleFormSend" :disabled="formData.length === 0"
                                                icon="pi pi-save"
                                                severity="success" label="Save changes"/>
                                    </div>
                                </div>
                            </template>
                            <Column style="font-weight: bold" field="Name" header="Name">
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()"
                                               class="p-column-filter" placeholder="Search user"/>
                                </template>
                            </Column>
                            <Column v-for="header in headers" :field="header" :key="header">
                                <template #header>
                                    <div class="mx-auto break-all">
                                        <div>
                                            {{ header }}
                                            <span v-tooltip.left="`Checked: ${checkedCount[header]}`" class="pi pi-info-circle"></span>
                                        </div>
                                    </div>
                                </template>
                                <template #body="{ index, field, data }">
                                    <div class="flex justify-center items-center h-full">
                                        <Checkbox v-model="data[field]" @change="extractData(data, index)"
                                                  :binary="true" v-tooltip.left="{ value: data[`editor_name_${field}`] ? `Edited by ${data[`editor_name_${field}`]} ${data[`updated_at_${field}`].split('T')[0]} ${data[`updated_at_${field}`].split('T')[1].split('.')[0]}` : 'No changes made', showDelay: 500, hideDelay: 0 }"/>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
