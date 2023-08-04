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
import reduce_tasks from "@/ReduceTasks.js";

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

    let tmp = reduce_tasks(tasks.value, userData.value, headers.value);

    tasks.value = tmp.tasks;
    userData.value = tmp.userData;
    headers.value = tmp.headers;

    for (const header of headers.value) {
        checkedCount.value[header] = 0;
    }

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
                                    <div class="md:ml-auto md:mr-4" style="cursor: not-allowed">
                                        <Button @click="handleFormSend" :disabled="formData.length === 0"
                                                icon="pi pi-save"
                                                severity="success" label="Save changes"/>
                                    </div>
                                    <div class="flex justify-content-end">
                                        <span class="p-input-icon-left">
                                            <i class="pi pi-search"/>
                                            <InputText v-model="filters['Name'].value" placeholder="Keyword Search"/>
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <Column style="font-weight: bold" field="Name" header="Name"/>
                            <Column v-for="header in headers" :field="header" :key="header" style="white-space: nowrap">
                                <template #header>
                                    <div class="mx-auto break-all">
                                        <div>
                                            {{ header }}
                                            <span v-tooltip.left="`Checked: ${checkedCount[header]}`"
                                                  class="pi pi-info-circle"></span>
                                        </div>
                                    </div>
                                </template>
                                <template #body="{ index, field, data }">
                                    <div class="flex justify-center items-center h-full">
                                        <Checkbox v-model="data[field]" @change="extractData(data, index)"
                                                  :binary="true"
                                                  v-tooltip.left="{ value: data[`editor_name_${field}`] ? `Edited by ${data[`editor_name_${field}`]} ${data[`updated_at_${field}`].split('T')[0]} ${data[`updated_at_${field}`].split('T')[1].split('.')[0]}` : 'No changes made', showDelay: 500, hideDelay: 0 }"/>
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
