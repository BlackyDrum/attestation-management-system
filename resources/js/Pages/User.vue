<script setup>
import {Head, Link, usePage, useForm} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';
import ErrorMessage from '@/Components/ErrorMessage.vue';

import {useConfirm} from "primevue/useconfirm";
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import InputText from 'primevue/inputtext';
import Button from "primevue/button";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import FileUpload from 'primevue/fileupload';


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

const search = ref("");
const empty = ref(false);
const editShow = ref(false);
const selectedUser = ref(null);
const createShow = ref(false);

const userForm = useForm({
    id: null,
    matriculation_number: null,
    name: null,
    email: null,
    password: null,
})

const userFormEdit = useForm({
    matriculation_number: null,
    name: null,
    email: null,
    password: null,
})

const userfileForm = useForm({
    userfile: null
})


onMounted(() => {
    search.value = page.props.search;
    empty.value = page.props.users.data.length === 0;
})

const handleSearchRequest = () => {
    axios.get(`/users/?search=${search.value}&response=true`)
        .then(response => {
            page.props.users = response.data;
            empty.value = page.props.users.data.length === 0;
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

const handleUserEdit = (user) => {
    editShow.value = true;
    selectedUser.value = user;
    userForm.reset('password');

    userForm.id = user.id;
    userForm.matriculation_number = user.matriculation_number;
    userForm.name = user.name;
    userForm.email = user.email;
}

const handleUserEditClose = () => {
    editShow.value = false;
    page.props.errors = {};
    userForm.password = null;
    userForm.wasSuccessful = false;
}

const sendEditForm = () => {
    if ((selectedUser.value.name === userForm.name && selectedUser.value.email === userForm.email && selectedUser.value.matriculation_number === userForm.matriculation_number) && !userForm.password)
        return;

    userForm.put('/users', {
        preserveScroll: true,
        onStart: () => {
            userForm.reset('password');
        },
        onSuccess: () => {
            selectedUser.value.id = userForm.id;
            selectedUser.value.matriculation_number = parseInt(userForm.matriculation_number);
            selectedUser.value.name = userForm.name;
            selectedUser.value.email = userForm.email;
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
                    life: 3000
                })
            }
        }
    });
}

const confirm2 = (userid, username) => {
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
                    for (let i = 0; i < page.props.users.data.length; i++) {
                        if (page.props.users.data[i].id === response.data.user_id) {
                            window.toast.add({
                                severity: 'success',
                                summary: 'Success',
                                detail: `User '${page.props.users.data[i].name}' with ID ${response.data.user_id} was deleted`,
                                life: 3000,
                            })
                            page.props.users.data.splice(i, 1);
                            empty.value = page.props.users.data.length === 0;
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

const handleCreateUserOpen = () => {
    userFormEdit.reset();
    createShow.value = true;
}

const handleCreateUserClose = () => {
    createShow.value = false;
    userFormEdit.wasSuccessful = false;
    page.props.errors = {};
}

const sendCreateForm = () => {
    userFormEdit.post('/users', {
        preserveScroll: true,
        onStart: () => userFormEdit.reset('password'),
        onSuccess: () => {
            userFormEdit.reset();
            window.toast.add({severity: 'success', summary: 'Success', detail: 'New user created', life: 3000})
        }
    });
}

const handleUpload = (event) => {
    userfileForm.post('/users/upload', {
        onStart: () => userfileForm.reset(),
        onSuccess: () => window.toast.add({
            severity: 'success',
            summary: 'File Uploaded',
            detail: 'User registration successful',
            life: 3000
        }),
        onError: () => {
            for (const error in page.props.errors) {
                window.toast.add({severity: 'error', summary: 'Error', detail: page.props.errors[error], life: 5000})
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
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="w-full text-right">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search"/>
                        <input-text type="text" class="rounded-xl text-black" placeholder="Search user"
                                    @input="handleSearchRequest" v-model="search"></input-text>
                    </span>
                </div>
                <div v-for="user in users.data" :key="user.id"
                     class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <div>
                        <div class="font-bold flex items-center text-white p-2 grid grid-cols-2">
                            <div class="break-all">{{ user.name }} <span v-if="user.admin" class="pi pi-android"></span>
                            </div>
                            <div class="ml-auto mr-5 flex flex-wrap  justify-content-center">
                                <div class="mr-4 md:hidden">
                                    <Button v-if="!user.admin" @click="confirm2(user.id, user.name)" icon="pi pi-trash"
                                            severity="danger"/>
                                </div>
                                <div class="mr-4 max-md:hidden">
                                    <Button v-if="!user.admin" @click="confirm2(user.id, user.name)" label="Delete"
                                            icon="pi pi-trash" severity="danger"/>
                                </div>
                                <div class="max-md:hidden">
                                    <Button @click="handleUserEdit(user)" label="Edit" icon="pi pi-user-edit"
                                            severity="success"/>
                                </div>
                                <div class="md:hidden">
                                    <Button @click="handleUserEdit(user)" icon="pi pi-user-edit" severity="success"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <ConfirmDialog ref="confirmDialog"
                               class="bg-white break-words p-4 custom-confirm-dialog rounded-md gap-8">
                </ConfirmDialog>
            </div>
        </div>
        <div v-if="empty" class="text-white flex">
            <div class="mx-auto text-3xl p-5">
                User Not Found
            </div>
        </div>
        <div class="text-white flex pb-5">
            <div class="mx-auto">
                <template v-for="links in users.links">
                    <Link v-if="links.url" :href="links.url + '&search=' + search"
                          class="lg:p-3 sm:p-1 md:p-2 max-sm:p-0.5">
                    <span v-html="links.label"
                          :class="{ 'bg-gray-600 p-2 rounded-xl': users.current_page === Number.parseInt(links.label) }"></span>
                    </Link>
                    <span v-else v-html="links.label"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Create new User -->
    <Dialog v-model:visible="createShow" :closable="false" header="Create new User"
            class="bg-gray-200 rounded-lg p-2 font-bold" :style="{ width: '90vw' }" :modal="true" :draggable="false">
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
                        <InputText :useGrouping="false" :disabled="userFormEdit.processing" required
                                   v-model="userFormEdit.matriculation_number"
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
                        <InputText :disabled="userFormEdit.processing" type="text" required v-model="userFormEdit.name"
                                   placeholder="Name"
                                   class="border border-black rounded-md p-1"/>
                    </div>
                    <error-message :show="errors.name">
                        {{ errors.name }}
                    </error-message>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-at mr-2"></i>
                        </span>
                        <InputText :disabled="userFormEdit.processing" type="email" required
                                   v-model="userFormEdit.email" placeholder="E-Mail"
                                   class="border border-black rounded-md p-1"/>
                    </div>
                    <error-message :show="errors.email">
                        {{ errors.email }}
                    </error-message>
                    <div class="p-inputgroup mt-2">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-lock mr-2"></i>
                        </span>
                        <InputText :disabled="userFormEdit.processing" type="password" v-model="userFormEdit.password"
                                   placeholder="Password"
                                   class="border border-black rounded-md p-1"/>
                    </div>
                    <error-message :show="errors.password">
                        {{ errors.password }}
                    </error-message>

                    <div class="mt-4 grid grid-cols-2">
                        <div class="justify-center">
                            <CustomProgressSpinner :processing="userFormEdit.processing"></CustomProgressSpinner>
                        </div>
                        <div class="flex justify-end" style="height: 3rem">
                            <primary-button class="max-md:mr-2 mr-5 disabled:cursor-not-allowed"
                                            :disabled="userFormEdit.processing || !userFormEdit.matriculation_number || !userFormEdit.name || !userFormEdit.email || !userFormEdit.password"
                                            @click="sendCreateForm">Create User
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
                    <em>To create multiple users simultaneously, you have the option of uploading a CSV file containing
                        columns for Matriculation Number, Name, Email, and Password.</em>
                </p>
                <div class="mt-4">
                    <FileUpload :disabled="userfileForm.processing" mode="basic" name="userfile[]" accept="text/csv"
                                :maxFileSize="1000000"
                                @uploader="handleUpload($event)"
                                @input="userfileForm.userfile = $event.target.files[0];" :multiple="false" :auto="false"
                                customUpload chooseLabel="Browse">
                        <template #empty>
                            <p>Drag and drop files to upload.</p>
                        </template>
                        <template #advanced>
                            <span>asd</span>
                        </template>
                    </FileUpload>
                    <error-message :show="errors.userfile">
                        {{ errors.userfile }}
                    </error-message>
                </div>
                <div class="mt-2 flex md:justify-end">
                    <secondary-button @click="handleCreateUserClose">Cancel</secondary-button>
                </div>
                <CustomProgressSpinner :processing="userfileForm.processing"></CustomProgressSpinner>
            </TabPanel>
        </TabView>
    </Dialog>

    <!-- Edit User -->
    <Dialog v-model:visible="editShow" :closable="false" v-if="selectedUser" :header="selectedUser.name"
            class="bg-gray-200 rounded-lg p-2 font-bold break-all" :style="{ width: '90vw' }" :modal="true"
            :draggable="false">
        <form @submit.prevent>
            <div class="p-inputgroup mt-2">
                <span class="p-inputgroup-addon">
                    <i class="pi pi-hashtag mr-2"></i>
                </span>
                <InputText :useGrouping="false" :disabled="userForm.processing" required
                           v-model="userForm.matriculation_number"
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
                <InputText :disabled="userForm.processing" type="text" required v-model="userForm.name"
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
                <InputText :disabled="userForm.processing" type="email" required v-model="userForm.email"
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
                <InputText :disabled="userForm.processing" type="password" v-model="userForm.password"
                           placeholder="New Password"
                />
            </div>
            <error-message :show="errors.password">
                {{ errors.password }}
            </error-message>

            <div class="mt-4 grid grid-cols-2 break-keep">
                <div class="justify-center">
                    <CustomProgressSpinner :processing="userForm.processing"></CustomProgressSpinner>
                </div>
                <div class="flex justify-end" style="height: 3rem">
                    <primary-button class="mr-5 disabled:cursor-not-allowed"
                                    :disabled="userForm.processing || (selectedUser.name === userForm.name && selectedUser.email === userForm.email && selectedUser.matriculation_number === parseInt(userForm.matriculation_number) && !userForm.password)"
                                    @click="sendEditForm">Save Changes
                    </primary-button>
                    <secondary-button @click="handleUserEditClose">Cancel</secondary-button>
                </div>
            </div>
        </form>
    </Dialog>
</template>
