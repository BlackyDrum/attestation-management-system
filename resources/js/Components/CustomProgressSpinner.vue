<script setup>
import ProgressSpinner from "primevue/progressspinner";


defineProps({
    processing: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <ProgressSpinner v-if="processing" style="width: 50px; height: 50px" strokeWidth="8"
                     fill="var(--surface-ground)" animationDuration=".5s"
                     aria-label="Custom ProgressSpinner"/>
</template>
