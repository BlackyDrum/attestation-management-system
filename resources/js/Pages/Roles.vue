<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import {computed, onMounted, ref} from "vue";

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';


defineProps({
    privileges: {
        type: Array
    },
})


const page = usePage();

const combinedData = ref(null);
const privilegesRaw = ref([]);
const formData = ref([]);


onMounted(() => {
    combinedData.value = reducedData;
    for (const item of combinedData.value[0].privilege) {
        privilegesRaw.value.push(item);
    }
    combinedData.value.sort((a, b) => a.role.localeCompare(b.role))
    privilegesRaw.value.sort((a, b) => a - b);
})

const checkEditRolePrivilege = computed(() => {
    for (const p of page.props.auth.privileges) {
        if (p.privilege === 'can_edit_role' && p.checked) {
            return true;
        }
    }
    return false;
})

const reducedData = page.props.privileges.reduce((accumulator, current) => {
    const existingRole = accumulator.find(item => item.role === current.role);
    if (!existingRole) {
        accumulator.push({
            role: current.role,
            privilege: [current.privilege],
            checked: [current.checked]
        });
    } else {
        // Find index of privilege in existing role
        const privilegeIndex = existingRole.privilege.indexOf(current.privilege);

        if (privilegeIndex === -1) {
            existingRole.privilege.push(current.privilege);
            existingRole.checked.push(current.checked);
        } else {
            existingRole.checked[privilegeIndex] = current.checked;
        }
    }
    return accumulator;
}, []);

const extractData = (role, index) => {
    for (let i = 0; i < formData.value.length; i++) {
        if (formData.value[i].role === role.role && formData.value[i].privilege === role.privilege[index]) {
            formData.value.splice(i,1)
        }
    }

    formData.value.push({
        checked: role.checked[index],
        role: role.role,
        privilege: role.privilege[index]
    })
}

const handleRoleUpdateForm = () => {
    window.axios.patch('/roles',{
        privileges: formData.value,
    })
        .then(response => {
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: "Privileges updated",
                life: 3000,
            })
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
            formData.value = [];
        })
}
</script>

<template>
    <Head title="Roles"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Roles</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8 ">
                <DataTable :value="privilegesRaw" stripedRows showGridlines>
                    <template #header>
                        <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="cursor-not-allowed md:ml-auto md:mr-4">
                                <Button icon="pi pi-save" :disabled="formData.length === 0"
                                        severity="success" label="Save changes"
                                        @click="handleRoleUpdateForm"/>
                            </div>
                        </div>
                    </template>
                    <Column class="font-bold">
                        <template #header>
                            Privileges
                        </template>
                        <template #body="{data}">
                            {{data}}
                        </template>
                    </Column>
                    <Column v-for="role in combinedData">
                        <template #header>
                            <div class="mx-auto break-words">
                                {{role.role}}
                            </div>
                        </template>
                        <template #body="{index, data}">
                            <div class="flex justify-center items-center h-full">
                                <Checkbox v-model="role.checked[role.privilege.findIndex(item => item === data)]"
                                          :binary="true" :disabled="!checkEditRolePrivilege && !page.props.auth.user.admin"
                                          @change="extractData(role, role.privilege.findIndex(item => item === data))"/>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
