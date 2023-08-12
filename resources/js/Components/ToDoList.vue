<script setup>
import {onMounted, ref} from 'vue';
import {usePage} from "@inertiajs/vue3";

import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';


defineProps({
    todos: {
        type: Array
    }
})


const page = usePage();

const tasks = ref([]);
const taskInput = ref(null);
const checked = ref([]);


onMounted(() => {
    for (const todo of page.props.todos) {
        tasks.value.push({
            id: todo.id,
            text: todo.task,
            checked: todo.checked
        })
    }
})

const handleNewTask = () => {
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
            })
}

const handleTaskChecking = (task) => {
    console.log(task)
}
</script>

<template>
    <span class="p-input-icon-left w-full">
    <i class="pi pi-plus" />
    <InputText class="w-full" placeholder="Enter Your Todo..." v-model="taskInput" @keydown.enter="handleNewTask"/>
    </span>
    <div class="w-full px-3 py-5 text-lg text-white font-semibold border-b border-gray-700" v-for="task in tasks">
        <div class="grid grid-cols-[10%,80%,10%] break-words">
            <div class="self-center">
                <Checkbox :value="task.id" v-model="task.checked" :binary="true" @change="handleTaskChecking(task)"/>
            </div>
            <div class="self-center decoration-2 decoration-black" :class="{'line-through': task.checked}">
                {{task.text}}
            </div>
            <div class="pi pi-trash self-center mx-auto text-red-600 cursor-pointer">
            </div>
        </div>
    </div>
</template>