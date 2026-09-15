<script setup lang="ts">
import GenericPieChart from '@/components/GenericPieChart.vue';

defineProps<{
    distribution: Record<string, number>;
}>();

const BAND_ORDER = ['Good', 'Normal', 'Unhealthy for Sensitive Groups', 'Unhealthy for All Groups', 'Very Unhealthy'];
const BAND_COLORS: Record<string, string> = {
    Good: '#22C55E',
    Normal: '#EAB308',
    'Unhealthy for Sensitive Groups': '#F59E0B',
    'Unhealthy for All Groups': '#EF4444',
    'Very Unhealthy': '#A855F7',
};
</script>

<template>
    <GenericPieChart :data="distribution" :colors="BAND_COLORS" :order="BAND_ORDER" center-label="days" />
</template>
