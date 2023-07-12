<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import {onMounted, ref} from 'vue';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import {useConfirm} from "primevue/useconfirm";


defineProps({
    users: {
        type: Object
    },
    search: {
        type: String
    }
})

onMounted(() => {
    search.value = page.props.search;
    empty.value = page.props.users.data.length === 0;
})


const page = usePage();
const confirm = useConfirm();

let search = ref("");
let empty = ref(false);

let errorShow = ref(false);
let errorMessage = ref(null);

let success = ref(false);
let successMessage = ref(null);

const handleSearchRequest = () => {
    axios.get(`/user/?search=${search.value}&response=true`)
        .then(response => {
            page.props.users = response.data;
            empty.value = page.props.users.data.length === 0;
        })
        .catch(error => {
            errorMessage.value = error;
            errorShow.value = true;
        })
}

const confirm2 = (userid) => {
    confirm.require({
        message: 'Do you want to delete this record?',
        header: 'Delete Confirmation',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',

        accept: () => {
            axios.delete('/user', {
                data: {
                    userid: userid,
                }
            })
                .then(response => {
                    if (!response.data.success) {
                        errorShow.value = true;
                        errorMessage.value = response.data.message;
                        return;
                    }
                    success.value = true;
                    for (let i = 0; i < page.props.users.data.length; i++) {
                        if (page.props.users.data[i].id === response.data.userid) {
                            successMessage.value = `User '${page.props.users.data[i].name}' with ID ${response.data.userid} was successfully deleted`;
                            page.props.users.data.splice(i, 1);
                            break;
                        }
                    }
                })
                .catch(error => {
                    errorShow.value = true;
                    errorMessage.value = error;
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

    <Dialog v-model:visible="errorShow" header="Error" class="bg-white rounded-lg p-2 font-bold"
            :style="{ width: '50vw' }" position="topleft" :modal="false" :draggable="false">
        <p class="text-red-600 font-medium">
            {{ errorMessage }}
        </p>
    </Dialog>

    <Dialog v-model:visible="success" header="Confirmation" class="bg-white rounded-lg p-2 font-bold"
            :style="{ width: '50vw' }" position="topleft" :modal="false" :draggable="false">
        <p class="text-green-600 font-medium">
            {{ successMessage }}
        </p>
    </Dialog>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="w-full text-right">
                    <input-text type="text" class="rounded-xl text-black lg:w-1/4" placeholder="Search user"
                                @input="handleSearchRequest" v-model="search"></input-text>
                </div>
                <div v-for="user in users.data" :key="user.id"
                     class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <Card>
                        <template #content>
                            <div class="font-bold flex items-center">
                                <span>{{ user.name }}</span>
                                <span class="ml-auto mr-5 flex flex-wrap gap- justify-content-center">
                                    <button v-if="!user.admin" @click="confirm2(user.id)"
                                            class="pi pi-trash mr-5 bg-red-600 rounded-md py-2 px-6 max-md:p-1 active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"></button>
                                    <button
                                        class="bg-green-600 rounded-md py-2 px-6 active:bg-gray-900 max-md:p-1 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        Edit
                                    </button>
                                </span>
                            </div>
                        </template>
                    </Card>
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
</template>

<style>
.p-dialog-title {
    font-weight: bold;
    font-size: larger;
}

.p-confirm-dialog-message {
    margin-left: 1vw;
    font-size: large;
    color: dimgray;
}

.p-confirm-dialog-icon {
    color: dimgray;
}

.p-dialog-footer {
    display: grid;
    grid-template-columns: auto auto;
}

.p-confirm-dialog-reject {
    color: royalblue;
}

.p-confirm-dialog-accept {
    color: white;
    background-color: red;
    padding-bottom: 1rem;
    padding-top: 1rem;
    border-radius: 0.375rem;
}
</style>
