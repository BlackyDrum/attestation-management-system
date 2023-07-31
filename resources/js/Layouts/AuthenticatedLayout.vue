<script setup>
import {onBeforeMount, onBeforeUnmount, onBeforeUpdate, onMounted, ref} from 'vue';
import {Link, usePage,router } from '@inertiajs/vue3';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import Imprint from '@/Components/Imprint.vue';
import PrivacyStatement from '@/Components/PrivacyStatement.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';


import {useToast} from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Toast from "primevue/toast";
import OverlayPanel from 'primevue/overlaypanel';
import Message from 'primevue/message';
import Button from "primevue/button";

const page = usePage();
window.toast = useToast();

onBeforeMount(() => {
    Echo.private(`notification.${page.props.auth.user.id}`)
        .listen('NotificationEvent', event => {
            window.toast.add({
                severity: 'info',
                summary: 'Info',
                detail: 'You have a new notification',
                life: 8000,
            })
            if (!page.props.auth.user.admin)
                router.reload();
        })
})

onBeforeUnmount(() => {
    Echo.leave(`notification.${page.props.auth.user.id}`);
});

onMounted(() => {
    notifications.value = page.props.auth.notifications;
})

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;
})

const notifications = ref([]);

const showingNavigationDropdown = ref(false);

const visibleImprint = ref(false);
const visiblePrivacy = ref(false);
const op = ref();


const togglePanel = (event) => {
    op.value.toggle(event);
}



const deleteNotification = (index, clear) => {
    axios.delete('/notifications', {
        data: {
            index: index,
            clearAll: clear,
        }
    })
        .then(response => {
            if (clear) {
                notifications.value = [];
                op.value.toggle();
            }
            else
                notifications.value.splice(index, 1);

            if (notifications.value.length === 0)
                op.value.toggle();

            router.reload('notifications');
        })
        .catch(error => {
            op.value.toggle();

            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 3000,
            })
        })
}

</script>

<template>
    <Toast position="top-left" class="break-words" />
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b  border-gray-100 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('attestations')" :active="route().current('attestations')">
                                    Attestations
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.admin" :href="route('user')"
                                         :active="route().current('user')">
                                    Users
                                </NavLink>
                            </div>

                        </div>
                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Settings Dropdown -->
                            <NavLink :no-link="true" @click="visiblePrivacy = true">
                                <div class="text-white mx-4 max-lg:hidden">
                                    Privacy Statement
                                </div>
                            </NavLink>
                            <NavLink :no-link="true" @click="visibleImprint = true">
                                <div class="text-white mx-4 max-lg:hidden">
                                    Imprint
                                </div>
                            </NavLink>
                            <div class="ml-4">
                                <button @click="togglePanel" v-badge="notifications.length" v-if="notifications.length > 0" class="pi pi-bell p-overlay-badge text-white" style="font-size: 1.5rem" />
                                <OverlayPanel class="w-[50%] max-lg:w-[60%]" ref="op">
                                    <div class="flex">
                                        <div>
                                            <h2>Notifications</h2>
                                        </div>
                                        <div class="ml-auto">
                                            <Button icon="pi pi-trash" severity="danger" @click="deleteNotification(-1,true)" label="Clear All"></Button>
                                        </div>
                                    </div>
                                    <Message :severity="notification.split('|')[0].trim().toLowerCase()" @close="deleteNotification(index, false)" v-for="(notification, index) in notifications" :key="notification">
                                        {{notification.split('|')[1].trim()}}
                                    </Message>
                                </OverlayPanel>
                            </div>
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }} <span v-if="$page.props.auth.user.admin" class=" ml-2 pi pi-android"></span>
                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Profile</DropdownLink>
                                        <button @click="visiblePrivacy = true" class="lg:hidden block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                                        >
                                            Privacy Statement
                                        </button>
                                        <button @click="visibleImprint = true" class="lg:hidden block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                                        >
                                            Imprint
                                        </button>
                                        <hr class="lg:hidden">
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('attestations')" :active="route().current('attestations')">
                            Attestations
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.admin" :href="route('user')"
                                           :active="route().current('user')">
                            User
                        </ResponsiveNavLink>
                        <NavLink :no-link="true" @click="visiblePrivacy = true">
                            <div class="text-white mx-4">
                                Privacy Statement
                            </div>
                        </NavLink>
                        <NavLink :no-link="true" @click="visibleImprint = true">
                            <div class="text-white mx-4">
                                Imprint
                            </div>
                        </NavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header"/>
                </div>
            </header>

            <!-- Dialogs -->
            <Dialog v-model:visible="visibleImprint" modal header="Imprint"
                    class="bg-gray-200 font-bold p-2 rounded-md"
                    :style="{ width: '75vw' }">
                <imprint></imprint>
            </Dialog>
            <Dialog v-model:visible="visiblePrivacy" modal header="Privacy Statement"
                    class="bg-gray-200 font-bold p-2 rounded-md"
                    :style="{ width: '75vw' }">
                <privacy-statement></privacy-statement>
            </Dialog>

            <!-- Page Content -->
            <main>
                <slot/>
            </main>
        </div>
    </div>
</template>
