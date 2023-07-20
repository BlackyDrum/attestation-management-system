<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, useForm, usePage} from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Dialog from "primevue/dialog";
import MultiSelect from 'primevue/multiselect';
import InputText from "primevue/inputtext";
import InputNumber from 'primevue/inputnumber';
import {onMounted, ref} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Dropdown from 'primevue/dropdown';
import ProgressSpinner from "primevue/progressspinner";
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tooltip from 'primevue/tooltip';

defineProps({
    users: {
        type: Object
    },
    semester: {
        type: Object
    },
    attestations: {
      type: Array
    },
    errors: {
        type: Object
    }
})

onMounted(() => {
    combinedData.value = combine();
    console.log(combinedData.value)
})

const page = usePage();

// Groups the database records into a compact array to work with
const combine = () => {
    const attestations = page.props.attestations;
    const combinedData = attestations.reduce((acc, item) => {
        const foundItem = acc.find(entry => (
            entry.id === item.id &&
            entry.subject_name === item.subject_name &&
            entry.subject_number === item.subject_number &&
            entry.creator_id === item.creator_id &&
            entry.semester === item.semester
        ));

        const task = {
            task_id: item.task_id,
            title: item.title,
            description: item.description,
            user_id: item.user_id,
            name: item.name,
            checked: item.checked,
        };

        if (foundItem) {
            const existingTaskIndex = foundItem.tasks.findIndex(f => (
                f.title === task.title &&
                f.description === task.description &&
                f.user_id === task.user_id &&
                f.name === task.name && f.checked === task.checked
            ));

            if (existingTaskIndex === -1) {
                foundItem.tasks.push(task);
            }
        } else {
            acc.push({
                id: item.id,
                subject_name: item.subject_name,
                subject_number: item.subject_number,
                creator_id: item.creator_id,
                semester: item.semester,
                tasks: [task],
            });
        }

        return acc;
    }, []);

    // Group the 'tasks' array by 'user_id' within each item of 'combinedData'
    combinedData.forEach(item => {
        const tasksGroupedByUserId = item.tasks.reduce((groups, task) => {
            if (!groups[task.user_id]) {
                groups[task.user_id] = [];
            }
            groups[task.user_id].push(task);
            return groups;
        }, {});
        item.tasks = Object.values(tasksGroupedByUserId);
    });

    // Sort the 'combinedData' array by 'subject_name'
    combinedData.sort((a, b) => {
        const subjectNameA = a.subject_name.toLowerCase();
        const subjectNameB = b.subject_name.toLowerCase();

        return subjectNameA < subjectNameB ? -1 : 1;
    });

    return combinedData;
};

const handleDialogClose = () => {
    showDialog.value = false;
}

const handleForm = () => {
    attestationForm
        .transform((data) => ({
            ...data,
            semester: data.semester ? data.semester.semester : null,
        }))
        .post('/attestations', {
            onSuccess: () => {
                reset();
                combinedData.value = combine();
            },
            onError: (error) => {
                for (let e in error) {
                    attestationForm.reset(e)
                }
            }
        })
}

const reset = () => {
    attestationForm.reset();
    taskCount.value = 1;

    for (let e in page.props.errors) {
        delete page.props.errors[e];
    }
}

const addTask = () => {
    attestationForm.attestations.push({
        id: taskCount.value++,
        title: null,
        description: null,
    })
}

const removeTask = () => {
    attestationForm.attestations.pop();
    taskCount.value--;
    delete page.props.errors['attestations.' + (taskCount.value - 1) + '.title'];
}


let attestationForm = useForm({
    users: null,
    subjectNumber: null,
    subjectName: null,
    semester: null,
    attestations: [],
})

let showDialog = ref(false);
let taskCount = ref(1);
let combinedData = ref(null);

</script>

<template>
    <Head title="Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="grid grid-cols-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        <span v-if="$page.props.auth.user.admin">Attestations Admin Panel</span>
                        <span v-else>My Attestations</span>
                    </h2>
                </div>
                <div class="ml-auto" v-if="$page.props.auth.user.admin">
                    <primary-button @click="showDialog = true">Create new Attestation</primary-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="$page.props.auth.user.admin" v-for="attestation in combinedData" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-10 p-4 rounded-lg">
                    <Card class="rounded-lg">
                        <template #title> {{attestation.subject_name}} ({{attestation.semester}}) </template>
                        <template #subtitle>Subject Number: {{attestation.subject_number}} </template>
                        <template #content>
                            <div class="flex flex-wrap justify-evenly gap-2">
                                <div class="w-1/2 max-md:w-full">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-user" />
                                        <InputText class="w-full" disabled :value="`Current Users: ${attestation.tasks.length}`" placeholder="Search"></InputText>
                                    </span>

                                </div>
                                <div class="w-1/2 max-md:w-full">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-file" />
                                        <InputText class="w-full" disabled :value="`Tasks: ${attestation.tasks[0].length}`" placeholder="Search"></InputText>
                                    </span>

                                </div>
                            </div>
                        </template>
                        <template #footer>
                            <Button icon="pi pi-file-edit" label="Edit" severity="success"/>
                            <Button icon="pi pi-trash" label="Delete" severity="danger" style="margin-left: 0.5em" />
                        </template>
                    </Card>
                </div>
                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                </div>
            </div>
        </div>

        <Dialog v-model:visible="showDialog" modal header="Create new Attestation" :style="{ width: '80vw' }">
            <form @submit.prevent="handleForm">
                <MultiSelect :loading="!$props.users" v-model="attestationForm.users" :options="users" filter optionLabel="name" placeholder="Select Users"
                             :maxSelectedLabels="3" :virtualScrollerOptions="{ itemSize: 44 }" class="w-full md:w-20rem" />
                <div v-if="errors.users" class="text-red-600">
                    {{errors.users}}
                </div>
                <div v-if="Object.keys(errors).some(key => key.startsWith('users.'))" class="text-red-600">
                    The selected User is invalid
                </div>
                <div class="grid xl:grid-cols-2 xl:gap-4">
                    <div class="my-4">
                    <span class="p-input-icon-right w-full">
                        <i class="pi pi-hashtag" />
                        <input-number v-model="attestationForm.subjectNumber" :useGrouping="false" class="w-full" placeholder="Subject Number"></input-number>
                    </span>
                        <div v-if="errors.subjectNumber" class="text-red-600">
                            {{errors.subjectNumber}}
                        </div>
                    </div>
                    <div class="my-4">
                    <span class="p-input-icon-right w-full">
                        <i class="pi pi-book" />
                        <input-text v-model="attestationForm.subjectName" class="w-full" placeholder="Subject Name"></input-text>
                    </span>
                        <div v-if="errors.subjectName" class="text-red-600">
                            {{errors.subjectName}}
                        </div>
                    </div>
                </div>
                <div>
                    <Dropdown v-model="attestationForm.semester" :options="semester" optionLabel="semester" placeholder="Select a semester" class="md:w-14rem" />
                    <div v-if="errors.semester" class="text-red-600">
                        {{errors.semester}}
                    </div>
                </div>
                <div class="mt-4">
                    <div v-for="task in attestationForm.attestations" :key="task.id" class="my-4 w-full">
                        <div class="mb-1 font-bold">
                            {{task.id}}. Attestation
                        </div>
                        <div>
                            <input-text v-model="attestationForm.attestations[task.id - 1].title" class="w-full" placeholder="Title"></input-text>
                            <div v-if="Object.keys(errors).some(key => key.startsWith('attestations.' + (task.id - 1) + '.title'))" class="text-red-600">
                                {{errors['attestations.' + (task.id - 1) + '.title']}}
                            </div>
                        </div>
                        <div class="mt-2">
                            <Textarea v-model="attestationForm.attestations[task.id - 1].description" placeholder="Description" autoResize rows="5" class="w-full"/>
                            <div v-if="Object.keys(errors).some(key => key.startsWith('attestations.' + (task.id - 1) + '.description'))" class="text-red-600">
                                {{errors['attestations.' + (task.id - 1) + '.description']}}
                            </div>
                        </div>
                    </div>
                </div>
                <Button @click="addTask" icon="pi pi-plus" aria-label="Filter" />
                <span v-if="attestationForm.attestations.length > 0" class="ml-3"><Button @click="removeTask" icon="pi pi-trash" severity="danger" aria-label="Filter" /></span>
                <div v-if="errors.attestations" class="text-red-600 mt-2">
                    {{errors.attestations}}
                </div>
                <div class="my-4 grid grid-cols-2">
                    <div class="justify-center">
                        <ProgressSpinner v-if="attestationForm.processing" style="width: 50px; height: 3rem" strokeWidth="8" fill="var(--surface-ground)"
                                         animationDuration=".5s" aria-label="Custom ProgressSpinner" />
                    </div>
                    <div class="flex justify-end" style="height: 3rem">
                        <primary-button class="mr-5" :disabled="attestationForm.processing">Save Changes</primary-button>
                        <secondary-button @click="handleDialogClose">Cancel</secondary-button>
                        <span class="ml-10 max-md:hidden">
                            <Button severity="danger" aria-label="Cancel" @click="reset">Reset</Button>
                        </span>
                    </div>
                </div>
            </form>
            <div v-if="attestationForm.wasSuccessful" class="text-green-600 font-bold">
                New Subject for attestation successfully created
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
