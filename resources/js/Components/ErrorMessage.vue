<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    }
})
</script>

<template>
    <div class="text-red-600 font-medium" v-if="show">
        <slot/>
    </div>
</template>
