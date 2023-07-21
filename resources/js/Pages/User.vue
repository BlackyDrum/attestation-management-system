<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

import { useConfirm } from "primevue/useconfirm";
import ProgressSpinner from 'primevue/progressspinner';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from "primevue/button";

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

onMounted(() => {
    search.value = page.props.search;
    empty.value = page.props.users.data.length === 0;
})

let userForm = useForm({
    id: null,
    name: null,
    email: null,
    password: null,
})

const page = usePage();
const confirm = useConfirm();

let search = ref("");
let empty = ref(false);

let errorShow = ref(false);
let errorMessage = ref(null);

let successShow = ref(false);
let successMessage = ref(null);

let editShow = ref(false);
let selectedUser = ref(null);

const handleSearchRequest = () => {
    axios.get(`/user/?search=${search.value}&response=true`)
        .then(response => {
            page.props.users = response.data;
            empty.value = page.props.users.data.length === 0;
        })
        .catch(error => {
            errorMessage.value = error.response.data.message;
            errorShow.value = true;
        })
}

const handleUserEdit = (user) => {
    editShow.value = true;
    selectedUser.value = user;

    userForm.id = user.id;
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
    if ((selectedUser.value.name === userForm.name && selectedUser.value.email === userForm.email) && !userForm.password) return;

    userForm.post('/user', {
        preserveScroll: true,
        onStart: () => userForm.reset('password'),
        onSuccess: () => {
            selectedUser.value.id = userForm.id;
            selectedUser.value.name = userForm.name;
            selectedUser.value.email = userForm.email;
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
            axios.delete('/user', {
                data: {
                    user_id: userid,
                }
            })
                .then(response => {
                    for (let i = 0; i < page.props.users.data.length; i++) {
                        if (page.props.users.data[i].id === response.data.user_id) {
                            successMessage.value = `User '${page.props.users.data[i].name}' with ID ${response.data.user_id} was successfully deleted`;
                            successShow.value = true;
                            page.props.users.data.splice(i, 1);
                            empty.value = page.props.users.data.length === 0;
                            break;
                        }
                    }
                })
                .catch(error => {
                    errorShow.value = true;
                    errorMessage.value = error.response.data.message;
                })
        },
        reject: () => {
            //...
        }
    });
};
</script>

<template>
    <Head title="User"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="w-full text-right">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <input-text type="text" class="rounded-xl text-black" placeholder="Search user"
                                @input="handleSearchRequest" v-model="search"></input-text>
                    </span>
                </div>
                <div v-for="user in users.data" :key="user.id"
                     class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <div>
                            <div class="font-bold flex items-center text-white p-2">
                                <span>{{ user.name }} <span v-if="user.admin" class="pi pi-android"></span></span>
                                <span class="ml-auto mr-5 flex flex-wrap  justify-content-center">
                                    <div class="mr-4 md:hidden">
                                        <Button v-if="!user.admin" @click="confirm2(user.id, user.name)" icon="pi pi-trash" severity="danger" />
                                    </div>
                                    <div class="mr-4 max-md:hidden">
                                        <Button v-if="!user.admin" @click="confirm2(user.id, user.name)" label="Delete" icon="pi pi-trash" severity="danger" />
                                    </div>
                                    <div class="max-md:hidden">
                                        <Button @click="handleUserEdit(user)" label="Edit" icon="pi pi-user-edit" severity="success" />
                                    </div>
                                    <div class="md:hidden">
                                        <Button @click="handleUserEdit(user)" icon="pi pi-user-edit" severity="success" />
                                    </div>
                                </span>
                            </div>
                    </div>
                </div>

                <ConfirmDialog ref="confirmDialog"
                               class="bg-white p-4 custom-confirm-dialog rounded-md gap-8"></ConfirmDialog>
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
                              :class="{'bg-gray-600 p-2 rounded-xl': users.current_page === Number.parseInt(links.label)}"></span>
                    </Link>
                    <span v-else v-html="links.label"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Dialogs -->
    <Dialog v-model:visible="errorShow" header="Error"
            class="w-1/2 max-md:w-full" position="topleft" :modal="false" :draggable="false">
        <p class="text-red-600 font-medium">
            {{ errorMessage }}
        </p>
    </Dialog>

    <Dialog v-model:visible="successShow" header="Confirmation"
            class="w-1/2 max-md:w-full" position="topleft" :modal="false" :draggable="false">
        <p class="text-green-600 font-medium">
            {{ successMessage }}
        </p>
    </Dialog>

    <Dialog v-model:visible="editShow" :closable="false" v-if="selectedUser" :header="selectedUser.name" class="bg-gray-200 rounded-lg p-2 font-bold"
            :style="{ width: '80vw' }" :modal="true" :draggable="false">
        <form @submit.prevent>
            <div class="p-inputgroup mt-2">
            <span class="p-inputgroup-addon">
                <i class="pi pi-user mr-2"></i>
            </span>
                <InputText type="text" required v-model="userForm.name" placeholder="Name" class="border border-black rounded-md p-1"/>
            </div>
            <div class="ml-6 text-red-600" v-if="errors.name">{{errors.name}}</div>
            <div class="p-inputgroup mt-2">
            <span class="p-inputgroup-addon">
                <i class="pi pi-at mr-2"></i>
            </span>
                <InputText type="email" required v-model="userForm.email" placeholder="E-Mail-Adress" class="border border-black rounded-md p-1"/>
            </div>
            <div class="ml-6 text-red-600" v-if="errors.email">{{errors.email}}</div>
            <div class="p-inputgroup mt-2">
            <span class="p-inputgroup-addon">
                <i class="pi pi-lock mr-2"></i>
            </span>
                <InputText type="password" v-model="userForm.password" placeholder="New Password" class="border border-black rounded-md p-1"/>
            </div>
            <div class="ml-6 text-red-600" v-if="errors.password">{{errors.password}}</div>
            <div class="mt-4 flex justify-end">
                <primary-button class="mr-5" :disabled="userForm.processing || (selectedUser.name === userForm.name && selectedUser.email === userForm.email && !userForm.password)" @click="sendEditForm">Save Changes</primary-button>
                <secondary-button @click="handleUserEditClose">Cancel</secondary-button>
            </div>
            <div v-if="userForm.progress">{{userForm.progress.percentage}}</div>
            <ProgressSpinner v-if="userForm.processing" style="width: 50px; height: 50px" strokeWidth="8" fill="var(--surface-ground)"
                             animationDuration=".5s" aria-label="Custom ProgressSpinner" />
            <div v-if="userForm.wasSuccessful" class="text-green-600 font-bold">
                User credentials successfully updated
            </div>
        </form>
    </Dialog>
</template>
