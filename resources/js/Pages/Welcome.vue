<template>
    <Landing :plans="plans" />
</template>

<script setup>
import Landing from './Central/Landing.vue'

defineProps({
    plans: {
        type: Array,
        default: () => []
    },
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
})
</script>
