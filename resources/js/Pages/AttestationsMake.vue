<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import {computed, onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';

import combine from '@/CombinedData.js';
import reduce_tasks from '@/ReduceTasks.js';

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
const dataTable = ref();
const userDataBackup = ref(null);

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

const canMakeAttestationPrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_make_attestation' && p.checked) {
            return true;
        }
    }
    return false;
})

const canRevokeAttestationPrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_revoke_attestation' && p.checked) {
            return true;
        }
    }
    return false;
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

    let index = 0;
    for (const user of userData.value) {
        for (const header of headers.value) {
            user[header] = !!user[header]; // Convert '1' to 'true' and '0' to 'false'
            if (user[header])
                checkedCount.value[header]++;
        }
        if (!user.user_id) {
            userData.value.splice(index, 1);
        }

        index++;
    }

    userWithMatriculationNumber.value = userData.value;
    userWithMatriculationNumber.value.map(user => {
        user.Name = `${user.Name} (${user.matriculation_number})`;
    })

    userDataBackup.value = JSON.parse(JSON.stringify(userData.value));
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
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: "Attestation updated",
                life: 3000,
            })
            router.reload({
                only: ['attestations']
            })
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
            router.reload();
        })
}

const exportCSV = () => {
    dataTable.value.exportCSV();
};

const resetForm = (field) => {
    userData.value = JSON.parse(JSON.stringify(userDataBackup.value));
    formData.value = [];
}
</script>

<template>
    <Head title="Make Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight break-words">Make
                Attestation for
                '{{ subject_name }}'</h2>
        </template>
        <div class="mt-5 sm:ml-8 text-gray-400 ">
            <Button icon="pi pi-arrow-left" class="h-10" severity="secondary"
                    label="Back" @click="router.get('/attestations')"></Button>
        </div>
        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <DataTable showGridlines stripedRows ref="dataTable"
                           :exportFilename="(subject_name + '_' + Date.now()).replaceAll(' ', '_')"
                           v-model:filters="filters" :value="userData" :paginator="true"
                           :rows="10">
                    <template #header>
                        <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <Button icon="pi pi-external-link" label="Export CSV"
                                        @click="exportCSV($event)"/>
                            </div>
                            <div class="flex flex-wrap gap-1 cursor-not-allowed lg:ml-auto md:mr-4">
                                <Button icon="pi pi-save"
                                        severity="warning" label="Reset"
                                        @click="resetForm"
                                        :disabled="formData.length === 0"/>
                                <Button icon="pi pi-save"
                                        severity="success" label="Save changes"
                                        @click="handleFormSend" :disabled="formData.length === 0"/>
                            </div>
                            <div class="flex justify-content-end">
                                        <span class="p-input-icon-left">
                                            <i class="pi pi-search"/>
                                            <InputText v-model="filters['Name'].value" placeholder="Search"/>
                                        </span>
                            </div>
                        </div>
                    </template>
                    <Column style="font-weight: bold" field="Name" header="Name"/>
                    <Column v-for="header in headers" :field="header" :key="header" style="white-space: nowrap">
                        <template #header>
                            <div class="mx-auto break-words">
                                <div>
                                    {{ header }}
                                    <span v-tooltip.left="`Checked: ${checkedCount[header]}`"
                                          class="pi pi-info-circle"></span>
                                </div>
                            </div>
                        </template>
                        <template #body="{ index, field, data }">
                            <div class="flex justify-center items-center h-full">
                                <Checkbox v-if="data['user_id']" v-model="data[field]"
                                          :binary="true"
                                          @change="extractData(data, index)"
                                          :disabled="(!canMakeAttestationPrivilege && !page.props.auth.user.admin) || !canRevokeAttestationPrivilege && !page.props.auth.user.admin && data[field]"
                                          v-tooltip.left="{ value: data[`editor_name_${field}`] ? `Edited by ${data[`editor_name_${field}`]} ${data[`updated_at_${field}`].split('T')[0]} ${data[`updated_at_${field}`].split('T')[1].split('.')[0]}` : 'No changes made', showDelay: 500, hideDelay: 0 }"/>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
