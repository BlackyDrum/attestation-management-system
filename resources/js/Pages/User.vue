<script setup>
import {Head, Link, usePage, useForm} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';

import { FilterMatchMode } from 'primevue/api';
import {useConfirm} from 'primevue/useconfirm';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Avatar from 'primevue/avatar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';


defineProps({
    users: {
        type: Object
    },
    search: {
        type: String
    },
    errors: {
        type: Object
    }
})

const page = usePage();
const confirm = useConfirm();

const searchValue = ref("");
const emptyUsers = ref(false);
const showUserEditDialog = ref(false);
const selectedUser = ref(null);
const showUserCreateDialog = ref(false);

const userEditForm = useForm({
    id: null,
    matriculation_number: null,
    name: null,
    email: null,
    password: null,
})

const userCreateForm = useForm({
    matriculation_number: null,
    name: null,
    email: null,
    password: null,
})

const userFileForm = useForm({
    userfile: null
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'country.name': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    representative: { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    verified: { value: null, matchMode: FilterMatchMode.EQUALS }
});


onMounted(() => {
    searchValue.value = page.props.search;
    emptyUsers.value = page.props.users.length === 0;
})

const handleUserEdit = (user) => {
    showUserEditDialog.value = true;
    selectedUser.value = user;
    userEditForm.reset('password');

    userEditForm.id = user.id;
    userEditForm.matriculation_number = user.matriculation_number;
    userEditForm.name = user.name;
    userEditForm.email = user.email;
}

const handleUserEditClose = () => {
    showUserEditDialog.value = false;
    page.props.errors = {};
    userEditForm.password = null;
    userEditForm.wasSuccessful = false;
}

const sendUserEditForm = () => {
    if ((selectedUser.value.name === userEditForm.name && selectedUser.value.email === userEditForm.email && selectedUser.value.matriculation_number === userEditForm.matriculation_number) && !userEditForm.password)
        return;

    userEditForm.put('/users', {
        preserveScroll: true,
        onStart: () => {
            userEditForm.reset('password');
        },
        onSuccess: () => {
            selectedUser.value.id = userEditForm.id;
            selectedUser.value.matriculation_number = parseInt(userEditForm.matriculation_number);
            selectedUser.value.name = userEditForm.name;
            selectedUser.value.email = userEditForm.email;
            window.toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'User credentials updated',
                life: 3000
            })
        },
        onError: () => {
            if (page.props.errors.id) {
                window.toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: page.props.errors.id,
                    life: 8000
                })
            }
        }
    });
}

const confirmUserDeletion = (userid, username) => {
    confirm.require({
        message: `Do you want to delete user '${username}'?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',

        accept: () => {
            axios.delete('/users', {
                data: {
                    user_id: userid,
                }
            })
                .then(response => {
                    for (let i = 0; i < page.props.users.length; i++) {
                        if (page.props.users[i].id === response.data.user_id) {
                            window.toast.add({
                                severity: 'success',
                                summary: 'Success',
                                detail: `User '${page.props.users[i].name}' with ID ${response.data.user_id} deleted`,
                                life: 3000,
                            })
                            page.props.users.splice(i, 1);
                            emptyUsers.value = page.props.users.length === 0;
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
        },
        reject: () => {
            //...
        }
    });
};

const handleCreateUserOpen = () => {
    userCreateForm.reset();
    showUserCreateDialog.value = true;
}

const handleCreateUserClose = () => {
    showUserCreateDialog.value = false;
    userCreateForm.wasSuccessful = false;
    page.props.errors = {};
}

const sendUserCreateForm = () => {
    userCreateForm.post('/users', {
        preserveScroll: true,
        onStart: () => userCreateForm.reset('password'),
        onSuccess: () => {
            userCreateForm.reset();
            window.toast.add({severity: 'success', summary: 'Success', detail: 'New user created', life: 3000})
        }
    });
}

const handleUserFileUpload = (event) => {
    userFileForm.post('/users/upload', {
        onStart: () => userFileForm.reset(),
        onSuccess: () => window.toast.add({
            severity: 'success',
            summary: 'File Uploaded',
            detail: 'User registration successful',
            life: 3000
        }),
        onError: () => {
            for (const error in page.props.errors) {
                window.toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: page.props.errors[error],
                    life: 8000
                })
            }
        }
    });
}
</script>

<template>
    <Head title="Users"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Users</h2>
                <div class="ml-auto">
                    <primary-button @click="handleCreateUserOpen">Create new User</primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8 max-w-7xl">
                <DataTable stateStorage="local" stateKey="dt-state-demo-session" stripedRows paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"  v-model:filters="filters" :value="users">
                    <template #header>
                        <div class="flex">
                            <span class="p-input-icon-left ml-auto">
                                <i class="pi pi-search"/>
                                <InputText v-model="filters['global'].value" placeholder="Search"/>
                            </span>
                        </div>
                    </template>
                    <Column>
                        <template #body>
                            <Avatar icon="pi pi-user" class="mr-2" size="xlarge" />
                        </template>
                    </Column>
                    <Column class="font-bold" field="name" header="Name"></Column>
                    <Column class="font-semibold" field="email" header="Email"></Column>
                    <Column class="font-semibold" field="matriculation_number" header="Matriculation Number"></Column>
                    <Column header="Role">
                        <template #body="{data}">
                            <Tag v-if="data.admin" severity="danger" value="Admin"></Tag>
                            <Tag v-else value="Student"></Tag>
                        </template>
                    </Column>
                    <Column>
                        <template #body="{data}">
                            <div class="flex flex-wrap justify-content-center ml-autog gap-2">
                                <div>
                                    <Button class="custom-button" icon="pi pi-user-edit"
                                            severity="info" @click="handleUserEdit(data)"/>
                                </div>
                                <div>
                                    <Button class="custom-button" icon="pi pi-envelope"
                                            severity="info" />
                                </div>
                                <div>
                                    <Button class="custom-button" icon="pi pi-trash"
                                            severity="danger"
                                            @click="confirmUserDeletion(data.id, data.name)"/>
                                </div>
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <ConfirmDialog class="bg-white break-words p-4 custom-confirm-dialog rounded-md gap-8" ref="confirmDialog"/>
            </div>
        </div>
        <div class="flex dark:text-white" v-if="emptyUsers">
            <div class="mx-auto text-3xl p-5">
                User Not Found
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Create new User -->
    <Dialog class="bg-gray-200 rounded-lg p-2 font-bold lg:w-[50%] md:w-[75%] w-[90%]"
            v-model:visible="showUserCreateDialog" :closable="false" header="Create new User"
            :modal="true" :draggable="false">
        <TabView>
            <TabPanel>
                <template #header>
                    <i class="pi pi-user mr-2 max-md:mr-1"></i>
                    <span class="max-md:text-xs">User</span>
                </template>
                <form @submit.prevent>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-hashtag mr-2"></i>
                        </span>
                        <InputText :useGrouping="false" :disabled="userCreateForm.processing" required
                                   v-model="userCreateForm.matriculation_number"
                                   placeholder="Matriculation Number"
                        />
                    </div>
                    <error-message :show="errors.matriculation_number">
                        {{ errors.matriculation_number }}
                    </error-message>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-user mr-2"></i>
                        </span>
                        <InputText class="border border-black rounded-md p-1"
                                   :disabled="userCreateForm.processing" type="text" required
                                   v-model="userCreateForm.name"
                                   placeholder="Name"/>
                    </div>
                    <error-message :show="errors.name">
                        {{ errors.name }}
                    </error-message>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-at mr-2"></i>
                        </span>
                        <InputText class="border border-black rounded-md p-1"
                                   :disabled="userCreateForm.processing" type="email" required
                                   v-model="userCreateForm.email" placeholder="E-Mail"/>
                    </div>
                    <error-message :show="errors.email">
                        {{ errors.email }}
                    </error-message>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-lock mr-2"></i>
                        </span>
                        <InputText class="border border-black rounded-md p-1"
                                   :disabled="userCreateForm.processing" type="password"
                                   v-model="userCreateForm.password"
                                   placeholder="Password"/>
                    </div>
                    <error-message :show="errors.password">
                        {{ errors.password }}
                    </error-message>

                    <div class="grid grid-cols-2 mt-4">
                        <div class="justify-center">
                            <CustomProgressSpinner :processing="userCreateForm.processing"></CustomProgressSpinner>
                        </div>
                        <div class="flex justify-end footer__buttonbar">
                            <primary-button class="max-md:mr-2 mr-5 disabled:cursor-not-allowed"
                                            :disabled="userCreateForm.processing || !userCreateForm.matriculation_number || !userCreateForm.name || !userCreateForm.email || !userCreateForm.password"
                                            @click="sendUserCreateForm">Create User
                            </primary-button>
                            <secondary-button @click="handleCreateUserClose">Cancel</secondary-button>
                        </div>
                    </div>
                </form>
            </TabPanel>
            <TabPanel>
                <template #header>
                    <i class="pi pi-upload mr-2 max-md:mr-1"></i>
                    <span class="max-md:text-xs">Upload</span>
                </template>
                <p class="font-bold">
                    <Message :closable="false">To create multiple users simultaneously, you have the option of uploading
                        a CSV file containing
                        columns for matriculation number, name, email, and password.
                    </Message>
                </p>
                <div class="mt-4">
                    <FileUpload :disabled="userFileForm.processing" mode="basic" name="userfile[]" accept="text/csv"
                                :maxFileSize="1e7"
                                @uploader="handleUserFileUpload($event)"
                                @input="userFileForm.userfile = $event.target.files[0];" :multiple="false" :auto="false"
                                customUpload chooseLabel="Browse">
                        <template #empty>
                            <p>Drag and drop files to upload.</p>
                        </template>
                        <template #advanced>
                            <span>asd</span>
                        </template>
                    </FileUpload>
                </div>
                <div class="flex mt-2 md:justify-end">
                    <secondary-button @click="handleCreateUserClose">Cancel</secondary-button>
                </div>
                <CustomProgressSpinner :processing="userFileForm.processing"></CustomProgressSpinner>
            </TabPanel>
        </TabView>
    </Dialog>

    <!-- Edit User -->
    <Dialog class="bg-gray-200 rounded-lg p-2 font-bold break-all lg:w-[50%] md:w-[75%] w-[90%]"
            v-model:visible="showUserEditDialog" :closable="false"
            v-if="selectedUser" :header="selectedUser.name" :modal="true"
            :draggable="false">
        <form @submit.prevent>
            <div class="p-inputgroup mt-2">
                <span class="p-inputgroup-addon">
                    <i class="pi pi-hashtag mr-2"></i>
                </span>
                <InputText :useGrouping="false" :disabled="userEditForm.processing" required
                           v-model="userEditForm.matriculation_number"
                           placeholder="Matriculation Number"
                />
            </div>
            <error-message :show="errors.matriculation_number">
                {{ errors.matriculation_number }}
            </error-message>
            <div class="p-inputgroup mt-2">
                <span class="p-inputgroup-addon">
                    <i class="pi pi-user mr-2"></i>
                </span>
                <InputText :disabled="userEditForm.processing" type="text" required v-model="userEditForm.name"
                           placeholder="Name"
                />
            </div>
            <error-message :show="errors.name">
                {{ errors.name }}
            </error-message>
            <div class="p-inputgroup mt-2">
                <span class="p-inputgroup-addon">
                    <i class="pi pi-at mr-2"></i>
                </span>
                <InputText :disabled="userEditForm.processing" type="email" required v-model="userEditForm.email"
                           placeholder="E-Mail"
                           c/>
            </div>
            <error-message :show="errors.email">
                {{ errors.email }}
            </error-message>
            <div class="p-inputgroup mt-2">
                <span class="p-inputgroup-addon">
                    <i class="pi pi-lock mr-2"></i>
                </span>
                <InputText :disabled="userEditForm.processing" type="password" v-model="userEditForm.password"
                           placeholder="New Password"
                />
            </div>
            <error-message :show="errors.password">
                {{ errors.password }}
            </error-message>

            <div class="grid grid-cols-2 mt-4 break-keep">
                <div class="justify-center">
                    <CustomProgressSpinner :processing="userEditForm.processing"></CustomProgressSpinner>
                </div>
                <div class="flex justify-end footer__buttonbar">
                    <primary-button class="mr-5 disabled:cursor-not-allowed"
                                    :disabled="userEditForm.processing || (selectedUser.name === userEditForm.name && selectedUser.email === userEditForm.email && selectedUser.matriculation_number === parseInt(userEditForm.matriculation_number) && !userEditForm.password)"
                                    @click="sendUserEditForm">Save Changes
                    </primary-button>
                    <secondary-button @click="handleUserEditClose">Cancel</secondary-button>
                </div>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
.footer__buttonbar {
    height: 3rem
}
.custom-button {
    padding: 0.5rem;
    width: 2rem;
    font-size: 0.5rem;
}
</style>
