<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage  } from '@inertiajs/vue3';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import {onMounted, onUpdated, ref, watch} from 'vue';
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    users: {
        type: Object
    },
    search: {
        type: String
    }
})
const page = usePage();
let search = ref("");
let searchError = ref("");
let empty = ref(false);

onMounted(() => {
    search.value = page.props.search;
    empty.value = page.props.users.data.length === 0;
})

function handleSearchRequest() {
    axios.get(`/user/search?search=${search.value}`)
        .then(response => {
            page.props.users = response.data;
            for (let link in page.props.users.links) {
                if (page.props.users.links[link].url === null)
                    continue;
                page.props.users.links[link].url = page.props.users.links[link].url.replace('/search','');
            }
            empty.value = page.props.users.data.length === 0;
        })
        .catch(error => {
            searchError.value = error;
        })
}
</script>

<template>
    <Head title="User" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="w-full text-right">
                    <input-text type="text" class="rounded-xl text-black lg:w-1/4" placeholder="Search user" @input="handleSearchRequest" v-model="search"></input-text>
                </div>
                <div v-for="user in users.data" :key="user.id" class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <Card>
                        <template #content>
                            <div class="font-bold flex items-center">
                                <span class="">{{user.name}}</span>
                                <span class="ml-auto mr-5">
                                    <button class="bg-red-600 rounded-md py-2 px-6 active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        Edit
                                    </button>
                                </span>
                            </div>
                        </template>
                    </Card>
                </div>
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
                    <Link v-if="links.url" :href="links.url + '&search=' + search" class="lg:p-3 sm:p-1 md:p-2 max-sm:p-0.5">
                        <span v-html="links.label" :class="{'bg-gray-600 p-2 rounded-xl': users.current_page === Number.parseInt(links.label)}"></span>
                    </Link>
                    <span v-else v-html="links.label"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
