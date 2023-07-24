<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {onMounted, ref} from "vue";

import Button from "primevue/button";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import { FilterMatchMode, FilterService } from 'primevue/api';

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
        if (!usersData[item.name]) {
            usersData[item.name] = { Name: item.name, user_id: item.user_id };
            uniqueTitles.forEach((title) => {
                usersData[item.name][title] = false;
            });
        }
    });

    tasks.value.forEach((tasksArray, index) => {
        tasksArray.forEach((task) => {
            usersData[task.name][task.title] = task.checked;
            usersData[task.name][`task_id_${task.title}`] = task.task_id;
        });
    });

    userData.value = Object.values(usersData);
    userData.value = userData.value.slice().sort((a,b) => {
        const surnameA = a.Name.split(' ').slice(-1)[0];
        const surnameB = b.Name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    })

    headers.value = Object.keys(userData.value[0]).filter((key) => key !== "Name" && key !== "user_id" && !key.startsWith('task_id'));
    console.log(userData.value);
})

const page = usePage();

let combinedData = ref(null);
let subject_name = ref("");
let tasks = ref([]);
let userData = ref(null);
let headers = ref(null);

const YOUR_FILTER = ref('YOUR FILTER');
const filters = ref({
    'Name': {value: null, matchMode: 'contains'},
});

FilterService.register(YOUR_FILTER.value, (value, filter) => {

    if (filter === undefined || filter === null || filter.trim() === '') {
        return true;
    }

    if (value === undefined || value === null) {
        return false;
    }

    return value.toString() === filter.toString();
});


</script>

<template>
    <Head title="Make Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Make Attestation for '{{subject_name}}'</h2>
        </template>
        <div class="mt-5 ml-5 text-gray-400 ">
            <Button @click="router.get('/attestations')" icon="pi pi-arrow-left" class="h-10" severity="secondary" label="Back"></Button>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div>
                        <DataTable v-model:filters="filters" filterDisplay="row" :value="userData" :paginator="true" :rows="10">

                            <Column field="Name" header="Name">
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter" placeholder="Search by name" />
                                </template>
                            </Column>
                            <Column v-for="header in headers" :field="header" :key="header">
                                <template #header>
                                    <div class="mx-auto">
                                        <div>{{ header }}</div>
                                    </div>
                                </template>
                                <template #body="{ index, field,data }">
                                    {{data}}
                                    <div class="flex justify-center items-center h-full">
                                        <Checkbox v-model="userData[index][field]" :binary="true"/>
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
