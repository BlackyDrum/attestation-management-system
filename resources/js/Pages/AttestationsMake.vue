<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, router, usePage} from '@inertiajs/vue3';
import {onMounted, ref} from "vue";

import Button from "primevue/button";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import {FilterService} from 'primevue/api';
import {useToast} from 'primevue/usetoast';

import combine from "@/CombinedData.js";

defineProps({
    attestations: {
        type: Object
    },
    id: {
        type: Number
    }
})

onMounted(() => {
    combinedData.value = combine(page.props.attestations);
    subject_name.value = combinedData.value[0].subject_name;
    tasks.value = combinedData.value[0].tasks;

    const uniqueTitles = Array.from(new Set(tasks.value.flat().map((item) => item.title)));

    const usersData = {};

    tasks.value.flat().forEach((item) => {
        const {name, title, task_id, checked, user_id} = item;
        const key = `${name}-${user_id}`;

        if (!usersData[key]) {
            usersData[key] = {Name: name, user_id};
            uniqueTitles.forEach((t) => {
                usersData[key][t] = false;
            });
        }

        usersData[key][title] = checked;
        usersData[key][`task_id_${title}`] = task_id;
    });

    userData.value = Object.values(usersData);
    userData.value = userData.value.slice().sort((a, b) => {
        const surnameA = a.Name.split(' ').slice(-1)[0];
        const surnameB = b.Name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });

    headers.value = Object.keys(userData.value[0]).filter((key) => key !== 'Name' && key !== 'user_id' && !key.startsWith('task_id'));
});

const page = usePage();
const toast = useToast();

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
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: "Successfully updated attestation",
                life: 3000,
            })
        })
        .catch(error => {
            toast.add({
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

const combinedData = ref(null);
const subject_name = ref("");
const tasks = ref([]);
const userData = ref(null);
const headers = ref(null);

const formData = ref([]);

const dt = ref();

const FILTER = ref('FILTER');
const filters = ref({
    'Name': {value: null, matchMode: 'contains'},
});

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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Make Attestation for
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
                            <Column field="Name" header="Name">
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()"
                                               class="p-column-filter" placeholder="Search by name"/>
                                </template>
                            </Column>
                            <Column v-for="header in headers" :field="header" :key="header">
                                <template #header>
                                    <div class="mx-auto">
                                        <div>{{ header }}</div>
                                    </div>
                                </template>
                                <template #body="{ index, field, data }">
                                    <div class="flex justify-center items-center h-full">
                                        <Checkbox v-model="data[field]" @change="extractData(data, index)"
                                                  :binary="true"/>
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
