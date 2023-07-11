<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage  } from '@inertiajs/vue3';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import {onMounted, onUpdated, ref, watch} from 'vue';

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
                <div class="w-full text-right ">
                    <input-text type="text" class="rounded-xl text-black w-1/4" placeholder="Search user" @input="handleSearchRequest" v-model="search"></input-text>
                </div>
                <div v-for="user in users.data" :key="user.id" class="bg-white text-white p-2 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg my-3">
                    <Card>
                        <template #content>
                            <div class="font-bold">
                                {{user.name}}
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
                    <Link v-if="links.url" :href="links.url + '&search=' + search" class="p-3">
                        <span v-html="links.label" :class="{'bg-gray-600 p-2 rounded-xl': users.current_page === Number.parseInt(links.label)}"></span>
                    </Link>
                    <span v-else v-html="links.label"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
