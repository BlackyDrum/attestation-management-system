<script setup>
import {onMounted, ref} from 'vue';
import {router, usePage} from "@inertiajs/vue3";

import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import ScrollPanel from 'primevue/scrollpanel';


defineProps({
    todos: {
        type: Array
    }
})


const page = usePage();

const tasks = ref([]);
const taskInput = ref(null);
const checked = ref([]);
const processing = ref(false);


onMounted(() => {
    for (const todo of page.props.todos) {
        tasks.value.push({
            id: todo.id,
            text: todo.task,
            checked: todo.checked
        })
        if (todo.checked)
            checked.value.push(todo)
    }
})

const handleNewTask = () => {
    if (processing.value)
        return;
    processing.value = true;

    if (taskInput.value)
        window.axios.post('/dashboard/todo', {
            task: taskInput.value
        })
            .then(response => {
                tasks.value.push({
                    id: response.data.id,
                    text: response.data.task,
                    checked: response.data.checked
                })
            })
            .catch(error => {
                window.toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response.data.message,
                    life: 8000,
                })
            })
            .then(() => {
                taskInput.value = null;
                processing.value = false;
            })
}

const handleTaskChecking = (task) => {
    if (processing.value)
        return;
    processing.value = true;

    window.axios.patch('/dashboard/todo', {
        id: task.id,
        checked: task.checked
    })
        .then(() => {
            if (task.checked)
                checked.value.push(task);
            else {
                let index = checked.value.findIndex((item) => item.id === task.id);
                if (index !== -1)
                    checked.value.splice(checked.value.findIndex((item) => item.id === task.id),1)
            }

        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
        })
        .then(() => {
            processing.value = false;
        })

}

const handleTaskDeletion = (task, index) => {
    if (processing.value)
        return;
    processing.value = true;

    window.axios.delete('/dashboard/todo', {
        data: {
            id: task.id
        }
    })
        .then(() => {
            tasks.value.splice(tasks.value.findIndex((item) => item.id === task.id), 1);
            let index = checked.value.findIndex((item) => item.id === task.id);
            if (index !== -1)
                checked.value.splice(index,1);
        })
        .catch(error => {
            window.toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 8000,
            })
        })
        .then(() => {
            processing.value = false;
        })
}
</script>

<template>
    <span class="p-input-icon-left w-full">
    <i class="pi pi-plus" />
    <InputText class="w-full" placeholder="Enter Your Todo..." v-model="taskInput" :disabled="processing" @keydown.enter="handleNewTask"/>
    </span>
    <ScrollPanel class="custom-scroll-panel">
        <div class="group w-full px-3 py-5 text-lg dark:text-white font-semibold border-b border-gray-700" v-if="tasks.length !== 0" v-for="(task, index) in tasks" :key="task.id">
            <div class="grid grid-cols-[10%,80%,10%] break-words">
                <div class="self-center">
                    <Checkbox :value="task.id" v-model="task.checked" :binary="true" :disabled="processing" @change="handleTaskChecking(task)"/>
                </div>
                <div class="self-center decoration-2 decoration-black" :class="{'line-through': task.checked}">
                    {{task.text}}
                </div>
                <div class="hidden group-hover:block my-auto ml-auto">
                    <button class="pi pi-trash self-center text-red-600 cursor-pointer" :disabled="processing" @click="handleTaskDeletion(task, index)"/>
                </div>
            </div>
        </div>
        <div v-else class="flex h-full">
            <div class="mx-auto my-auto">
                <div class="text-gray-700 text-center">
                    <div class="pi pi-book custom-icon"></div>
                </div>
                <div class="text-gray-500 text-center mt-4">
                    Everything done!
                </div>
            </div>
        </div>
    </ScrollPanel>
    <div class="dark:text-white">
        {{tasks.length - checked.length}} tasks left
    </div>
</template>

<style scoped>
.custom-scroll-panel {
    height: 20rem;
}
.custom-icon {
    font-size: 10rem
}
</style>
