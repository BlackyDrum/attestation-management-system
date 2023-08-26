<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import {computed, onBeforeUpdate, onMounted, ref} from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import {useConfirm} from 'primevue/useconfirm';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Editor from 'primevue/editor';
import Chart from 'primevue/chart';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';

import combine from '@/CombinedData.js';
import reduce_tasks from '@/ReduceTasks.js';

defineProps({
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


const page = usePage();
const confirm = useConfirm();

const showAttestationInfoDialog = ref(false);
const combinedData = ref(null);
const subject_name = ref("");
const tasks = ref([]);
const userData = ref([]);
const headers = ref(null);
const descriptions = ref([]);
const chartData = ref([]);
const chart = ref("Polar");


onMounted(() => {
    combinedData.value = combine(page.props.attestations);
    chartData.value = [];
    setupChart();
})

onBeforeUpdate(() => {
    combinedData.value = combine(page.props.attestations);
    chartData.value = [];
    setupChart();
})

const noAttestations = computed(() => {
    return page.props.attestations.length === 0 || (Array.isArray(combinedData.value) && combinedData.value.length === 0)
})

const combinedDataSorted = computed(() => {
    return id => {
        if (combinedData.value) {
            return combinedData.value.filter(item => item.semester_id === id);
        }
    }
})

const handleAttestationInfo = (attestation, index) => {
    showAttestationInfoDialog.value = true;
    subject_name.value = combinedData.value[index].subject_name;
    tasks.value = combinedData.value[index].tasks;
    descriptions.value = [];

    let tmp = reduce_tasks(tasks.value, userData.value, headers.value);

    tasks.value = tmp.tasks;
    userData.value = tmp.userData;
    headers.value = tmp.headers;

    for (let i = 0; i < headers.value.length; i++) {
        descriptions.value.push(combinedData.value[index].tasks[0][i].description)
    }
}

const setupChart = () => {
    for (let i = 0; i < combinedData.value.length; i++) {
        chartData.value.push({
            labels: [],
            datasets: [
                {
                    label: 'Checked',
                    data: [],
                    backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
                    borderColor: ['rgb(255, 159, 64)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
                    borderWidth: 1
                }
            ]
        })
        for (const item of combinedData.value[i].tasks[0]) {
            chartData.value[i].labels.push(item.title)
            chartData.value[i].datasets[0].data.push(0);
        }
        for (const user of combinedData.value[i].tasks) {
            for (const item of user) {
                if (item.checked) {
                    let index = chartData.value[i].labels.findIndex((label) => label === item.title);
                    chartData.value[i].datasets[0].data[index]++;
                }
            }
        }
    }
}
</script>

<template>
    <Head title="My Attestations"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">My Attestations</h2>
        </template>

        <div class="py-12">
            <div v-if="!noAttestations" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Accordion :activeIndex="0">
                    <AccordionTab v-for="(s, index1) in semester">
                        <template #header>
                            <i class="pi pi-calendar mr-2"></i>
                            <span>{{s.semester}}</span>
                        </template>
                        <Accordion class="shadow-xl">
                            <AccordionTab v-for="(attestation, index) in combinedDataSorted(s.id)" :key="`${s.id}_${index1}_${attestation.id}_${index}`" :header="`${attestation.subject_name} (${attestation.subject_number})`">
                                <div>
                                    <div class="shadow-xl mb-4">
                                        <Card class="rounded-lg border">
                                            <template #title> {{ attestation.subject_name }} ({{ attestation.semester }})</template>
                                            <template #subtitle>Subject Number: {{ attestation.subject_number }}</template>
                                            <template #content>
                                                <div class="flex flex-wrap justify-evenly gap-2">
                                                    <div class="w-1/2 max-md:w-full">
                                                        <span class="p-input-icon-left w-full">
                                                            <i class="pi pi-file"/>
                                                            <InputText class="w-full" disabled
                                                                       placeholder="Search"
                                                                       :value="`Tasks: ${attestation.tasks[0].length}`"/>
                                                        </span>
                                                    </div>
                                                </div>
                                                <Chart type="bar" :data="chartData[attestation.index]"/>
                                            </template>
                                            <template #footer>
                                                <div class="grid grid-cols-2 max-md:grid-cols-1">
                                                    <div>
                                                        <Button icon="pi pi-info-circle"
                                                                label="Info" severity="success"
                                                                @click="handleAttestationInfo(attestation, attestation.index)"/>
                                                    </div>
                                                </div>
                                            </template>
                                        </Card>
                                    </div>
                                </div>
                            </AccordionTab>
                        </Accordion>
                    </AccordionTab>
                </Accordion>
            </div>
        </div>

        <template
            v-if="noAttestations">
            <div class="text-gray-700 text-center">
                <div class="pi pi-book custom-icon"></div>
            </div>
            <div class="text-gray-500 text-center mt-4">
                No Attestations assigned to you
            </div>
        </template>
    </AuthenticatedLayout>

    <Dialog v-model:visible="showAttestationInfoDialog" modal :header="subject_name"
            :style="{ width: '90vw' }">
        <TabView :scrollable="true">
            <TabPanel>
                <template #header>
                    <i class="pi pi-calendar mr-2"></i>
                    <span>Attestation</span>
                </template>
                <DataTable showGridlines stripedRows :value="userData">
                    <Column field="Name" header="Name"></Column>
                    <Column v-for="header in headers" :field="header" :key="header">
                        <template #header="{ index }">
                            <div class="mx-auto">
                                <div>{{ header }}</div>
                            </div>
                        </template>
                        <template #body="{ index, field, data }">
                            <div class="flex justify-center items-center h-full">
                                <Checkbox disabled v-model="data[field]" :binary="true"/>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </TabPanel>
            <TabPanel v-for="(header, index1) in headers" :key="header">
                <template #header>
                    <i class="pi pi-file-edit mr-2"></i>
                    <span class="font-medium whitespace-nowrap">{{ header }}</span>
                </template>
                <Editor class="h-full w-full" readonly
                        v-if="descriptions[index1]"
                        v-model="descriptions[index1]">
                    <template #toolbar>
                        <span></span>
                    </template>
                </Editor>
                <span v-else>
                    <em>No Description available.</em>
                </span>
            </TabPanel>
        </TabView>
    </Dialog>
</template>
