<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    aqi: number;
    color: string;
}>();

const angle = computed(() => Math.min(props.aqi, 300) * 1.2);

const gaugeStyle = computed(() => ({
    background: `conic-gradient(
        #22C55E 0deg 60deg,
        #EAB308 60deg 120deg,
        #F59E0B 120deg 180deg,
        #EF4444 180deg 240deg,
        #A855F7 240deg 360deg
    )`,
}));

const markerStyle = computed(() => ({
    transform: `rotate(${angle.value}deg) translate(0, -110px) rotate(-${angle.value}deg)`,
    borderColor: props.color,
}));
</script>

<template>
    <div class="relative flex h-64 w-64 items-center justify-center rounded-full" :style="gaugeStyle">
        <div class="absolute inset-3 rounded-full bg-airzen-card"></div>
        <div class="absolute left-1/2 top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow" :style="markerStyle"></div>
        <div class="relative flex flex-col items-center font-heading">
            <span class="text-5xl font-bold" :style="{ color: props.color }">{{ Math.round(props.aqi) }}</span>
            <span class="text-sm font-medium text-airzen-muted">AQI</span>
        </div>
    </div>
</template>
