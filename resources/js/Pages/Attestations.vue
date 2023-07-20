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


let attestationForm = useForm({
    users: null,
    subjectNumber: null,
    subjectName: null,
    semester: null,
    attestations: [],
})

const page = usePage();

let showDialog = ref(false);

let fieldCount = ref(1);

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

        const field = {
            field_id: item.field_id,
            title: item.title,
            description: item.description,
            user_id: item.user_id,
            name: item.name,
            checked: item.checked,
        };

        if (foundItem) {
            const existingFieldIndex = foundItem.fields.findIndex(f => (
                f.title === field.title &&
                f.description === field.description &&
                f.user_id === field.user_id &&
                f.name === field.name && f.checked === field.checked
            ));

            if (existingFieldIndex === -1) {
                foundItem.fields.push(field);
            }
        } else {
            acc.push({
                id: item.id,
                subject_name: item.subject_name,
                subject_number: item.subject_number,
                creator_id: item.creator_id,
                semester: item.semester,
                fields: [field],
            });
        }

        return acc;
    }, []);

    // Group the 'fields' array by 'user_id' within each item of 'combinedData'
    combinedData.forEach(item => {
        const fieldsGroupedByUserId = item.fields.reduce((groups, field) => {
            if (!groups[field.user_id]) {
                groups[field.user_id] = [];
            }
            groups[field.user_id].push(field);
            return groups;
        }, {});
        item.fields = Object.values(fieldsGroupedByUserId);
    });

    // Sort the 'combinedData' array by 'subject_name'
    combinedData.sort((a, b) => {
        const subjectNameA = a.subject_name.toLowerCase();
        const subjectNameB = b.subject_name.toLowerCase();

        if (subjectNameA < subjectNameB) {
            return -1;
        }
        if (subjectNameA > subjectNameB) {
            return 1;
        }
        return 0;
    });

    return combinedData;
};

let combinedData = ref(combine());
console.log(combinedData.value)
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
    fieldCount.value = 1;

    for (let e in page.props.errors) {
        delete page.props.errors[e];
    }
}

const addField = () => {
    attestationForm.attestations.push({
        id: fieldCount.value++,
        title: null,
        description: null,
    })
}

const removeField = () => {
    attestationForm.attestations.pop();
    fieldCount.value--;
    delete page.props.errors['attestations.' + (fieldCount.value - 1) + '.title'];
}

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
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    {{combinedData}}
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
                    <div v-for="field in attestationForm.attestations" :key="field.id" class="my-4 w-full">
                        <div class="mb-1 font-bold">
                            {{field.id}}. Attestation
                        </div>
                        <div>
                            <input-text v-model="attestationForm.attestations[field.id - 1].title" class="w-full" placeholder="Title"></input-text>
                            <div v-if="Object.keys(errors).some(key => key.startsWith('attestations.' + (field.id - 1) + '.title'))" class="text-red-600">
                                {{errors['attestations.' + (field.id - 1) + '.title']}}
                            </div>
                        </div>
                        <div class="mt-2">
                            <Textarea v-model="attestationForm.attestations[field.id - 1].description" placeholder="Description" autoResize rows="5" class="w-full"/>
                            <div v-if="Object.keys(errors).some(key => key.startsWith('attestations.' + (field.id - 1) + '.description'))" class="text-red-600">
                                {{errors['attestations.' + (field.id - 1) + '.description']}}
                            </div>
                        </div>
                    </div>
                </div>
                <Button @click="addField" icon="pi pi-plus" aria-label="Filter" />
                <span v-if="attestationForm.attestations.length > 0" class="ml-3"><Button @click="removeField" icon="pi pi-trash" severity="danger" aria-label="Filter" /></span>
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
                        <span class="ml-10">
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
