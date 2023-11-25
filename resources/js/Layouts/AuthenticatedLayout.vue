<script setup>
import {computed, onBeforeMount, onBeforeUnmount, onBeforeUpdate, onMounted, ref} from 'vue';
import {Link, usePage, router} from '@inertiajs/vue3';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import Imprint from '@/Components/Imprint.vue';
import PrivacyStatement from '@/Components/PrivacyStatement.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

import {useToast} from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import OverlayPanel from 'primevue/overlaypanel';
import Message from 'primevue/message';
import Button from 'primevue/button';
import ScrollTop from 'primevue/scrolltop';

import checkPrivilege from "@/CheckPrivilege.js";


const page = usePage();
window.toast = useToast();

const notifications = ref([]);
const privileges = ref([]);
const showNavigationDropdown = ref(false);
const showImprint = ref(false);
const showPrivacyStatement = ref(false);
const notificationOverlayPanel = ref();


onBeforeMount(() => {
    privileges.value = page.props.auth.privileges;
    notifications.value = page.props.auth.notifications;

    Echo.private(`notification.${page.props.auth.user.id}`)
        .listen('NotificationEvent', event => {
            window.toast.add({
                severity: 'info',
                summary: 'Info',
                detail: 'You have a new notification',
                life: 8000,
            })
            // Do not partially reload the data for the user who initiated the notification,
            // because it leads to unexpected behaviour
            if (event.initiator_id !== page.props.auth.user.id)
                router.reload();
        })
})

onBeforeUnmount(() => {
    Echo.leave(`notification.${page.props.auth.user.id}`);
});

onBeforeUpdate(() => {
    notifications.value = page.props.auth.notifications;
})

const toggleNotificationOverlayPanel = (event) => {
    if (notifications.value.length !== 0)
        notificationOverlayPanel.value.toggle(event);
}

const deleteNotification = (index, clear) => {
    axios.delete('/notifications', {
        data: {
            index: index,
            clearAll: clear,
        }
    })
        .then(response => {
            if (clear)
                notifications.value.splice(0,notifications.value.length);
            else
                notifications.value.splice(index, 1);

            if (notifications.value.length === 0)
                notificationOverlayPanel.value.toggle();

            //router.reload();
        })
        .catch(error => {
            notificationOverlayPanel.value.toggle();

            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
        })
}
</script>

<template>
    <Toast position="top-left" class="break-words"/>
    <ScrollTop />
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white border-b border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-5 lg:-my-px lg:flex lg:ml-10 md:ml-5">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    <span class="pi pi-inbox mr-1"></span>
                                    Dashboard
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_subject_page', privileges)" :href="route('attestations')" :active="route().current('attestations')">
                                    <span class="pi pi-book mr-1"></span>
                                    Subjects
                                </NavLink>
                                <NavLink :href="route('my_attestations')" :active="route().current('my_attestations')">
                                    <span class="pi pi-map mr-1"></span>
                                    My Attestations
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_user_page', privileges)" :href="route('user')"
                                         :active="route().current('user')">
                                    <span class="pi pi-users mr-1"></span>
                                    Users
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_role_page', privileges)" :href="route('roles')"
                                         :active="route().current('roles')">
                                    <span class="pi pi-paperclip mr-1"></span>
                                    Roles
                                </NavLink>
                            </div>

                        </div>
                        <div class="hidden xl:flex xl:items-center xl:ml-6">
                            <!-- Settings Dropdown -->
                            <NavLink class="max-lg:hidden" :no-link="true" @click="showPrivacyStatement = true">
                                <div class="dark:text-white mx-4 max-lg:hidden">
                                    <span class="pi pi-flag"></span>
                                    Privacy Statement
                                </div>
                            </NavLink>
                            <NavLink class="max-lg:hidden" :no-link="true" @click="showImprint = true">
                                <div class="dark:text-white mx-4">
                                    <span class="pi pi-info-circle"></span>
                                    Imprint
                                </div>
                            </NavLink>
                            <div class="ml-4 max-md:hidden">
                                <button class="pi pi-bell p-overlay-badge dark:text-white text-gray-500 mr-0.5"
                                        style="font-size: 1.5rem"
                                        v-badge="notifications.length !== 0 ? notifications.length : '0'"
                                        @click="toggleNotificationOverlayPanel"/>
                                <OverlayPanel class="w-[40%] max-lg:w-[60%]" ref="notificationOverlayPanel">
                                    <div class="flex">
                                        <div class="font-bold">
                                            Notifications
                                        </div>
                                        <div class="ml-auto mr-2 cursor-pointer font-semibold text-blue-700" @click="deleteNotification(-1,true)">
                                            Clear all
                                        </div>
                                    </div>
                                    <div class="text-sm" v-for="(notification, index) in notifications" :key="notification">
                                        <template v-if="index < 4">
                                            <Message :severity="notification.split('|')[0].trim().toLowerCase()"
                                                     @close="deleteNotification(index, false)"
                                            >
                                                <div>{{ notification.split('|')[1].trim() }}</div>
                                                <div class="font-medium text-gray-900/70">
                                                    {{ notification.split('|')[2].trim() }}
                                                </div>
                                            </Message>
                                        </template>
                                    </div>
                                </OverlayPanel>
                            </div>
                            <div class="relative ml-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm bg-white leading-4 transition ease-in-out duration-150 font-medium rounded-md text-gray-500 dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                                type="button"
                                            >
                                                {{ $page.props.auth.user.name }} <span
                                                class="pi pi-wrench ml-2" v-if="$page.props.auth.user.admin"></span>
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
                                        <DropdownLink :href="route('profile.edit')">
                                            <span class="pi pi-user mr-1"></span>
                                            Profile
                                        </DropdownLink>
                                        <button class="lg:hidden block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                                                @click="showPrivacyStatement = true"
                                        >
                                            <span class="pi pi-flag"></span>
                                            Privacy Statement
                                        </button>
                                        <button class="lg:hidden block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                                                @click="showImprint = true"
                                        >
                                            <span class="pi pi-info-circle"></span>
                                            Imprint
                                        </button>
                                        <hr class="lg:hidden">
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            <span class="pi pi-arrow-left mr-1"></span>
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center -mr-2 xl:hidden">
                            <button
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                                @click="showNavigationDropdown = !showNavigationDropdown"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showNavigationDropdown,
                                            'inline-flex': !showNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showNavigationDropdown,
                                            'inline-flex': showNavigationDropdown,
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
                    :class="{ block: showNavigationDropdown, hidden: !showNavigationDropdown }"
                    class="xl:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            <span class="pi pi-inbox mr-1"></span>
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_subject_page', privileges)" :href="route('attestations')" :active="route().current('attestations')">
                            <span class="pi pi-book mr-1"></span>
                            Subjects
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('my_attestations')" :active="route().current('my_attestations')">
                            <span class="pi pi-map mr-1"></span>
                            My Attestations
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_user_page', privileges)" :href="route('user')"
                                           :active="route().current('user')">
                            <span class="pi pi-users mr-1"></span>
                            Users
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.admin || checkPrivilege('can_access_role_page', privileges)" :href="route('roles')"
                                           :active="route().current('roles')">
                            <span class="pi pi-paperclip mr-1"></span>
                            Roles
                        </ResponsiveNavLink>
                        <NavLink :no-link="true" @click="showPrivacyStatement = true">
                            <div class="dark:text-white mx-4">
                                Privacy Statement
                            </div>
                        </NavLink>
                        <NavLink :no-link="true" @click="showImprint = true">
                            <div class="dark:text-white mx-4">
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
                            <ResponsiveNavLink :href="route('profile.edit')">
                                <span class="pi pi-user mr-1"></span>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                <span class="pi pi-arrow-left mr-1"></span>
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
            <Dialog modal header="Imprint" :draggable="false"
                    class="bg-gray-200 font-bold p-2 rounded-md"
                    :style="{ width: '90vw' }" v-model:visible="showImprint">
                <imprint></imprint>
            </Dialog>
            <Dialog modal header="Privacy Statement" :draggable="false"
                    class="bg-gray-200 font-bold p-2 rounded-md"
                    :style="{ width: '90vw' }" v-model:visible="showPrivacyStatement">
                <privacy-statement></privacy-statement>
            </Dialog>

            <!-- Page Content -->
            <main>
                <slot/>
            </main>
        </div>
    </div>
</template>
