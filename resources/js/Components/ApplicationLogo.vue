<script setup>
import { ref } from 'vue';

defineProps({
    size: {
        type: Number,
        default: 100
    }
})

const uri = ref("/static/img/logo.png");
</script>

<template>
    <img :src="uri" width="500" alt="logo">
</template>
