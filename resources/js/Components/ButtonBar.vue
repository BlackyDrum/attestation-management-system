<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomProgressSpinner from '@/Components/CustomProgressSpinner.vue';


defineProps({
    disable_primary: {
        type: Boolean
    },
    processing: {
        type: Boolean
    }

})
</script>

<template>
    <div class="grid grid-cols-2 my-4">
        <div class="justify-center">
            <CustomProgressSpinner :processing="processing"></CustomProgressSpinner>
        </div>
        <div class="flex justify-end footer__buttonbar">
            <primary-button class="mr-5 disabled:cursor-not-allowed"
                            :disabled="disable_primary">
                <slot name="primary"></slot>
            </primary-button>
            <secondary-button @click="$emit('handleClose')">Cancel</secondary-button>
            <slot name="additional"></slot>
        </div>
    </div>
</template>

<style scoped>
.footer__buttonbar {
    height: 3rem
}
</style>

