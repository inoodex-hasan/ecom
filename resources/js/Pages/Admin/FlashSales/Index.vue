<script setup>
import CampaignsIndex from '@/Pages/Admin/Campaigns/Index.vue';

defineProps({
    flashSales: Array,
    metrics: Object,
    availableProducts: Array,
});
</script>

<template>
    <CampaignsIndex v-bind="$props" />
</template>
