<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    aqi: number;
    color: string;
}>();

// Tweens the ring and number to the new AQI whenever a poll brings fresh data,
// instead of jumping. Starts at the real value on mount - never animates in
// from zero.
const displayedAqi = ref(props.aqi);
let frame: number | undefined;

watch(
    () => props.aqi,
    (next, previous) => {
        const from = previous;
        const delta = next - from;
        const start = performance.now();
        const duration = 500;

        if (frame) cancelAnimationFrame(frame);

        const tick = (now: number) => {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            displayedAqi.value = from + delta * eased;
            if (t < 1) frame = requestAnimationFrame(tick);
        };

        frame = requestAnimationFrame(tick);
    },
);

onBeforeUnmount(() => {
    if (frame) cancelAnimationFrame(frame);
});

const angle = computed(() => Math.min(displayedAqi.value, 300) * 1.2);

const gaugeStyle = {
    background: `conic-gradient(
        #22C55E 0deg 60deg,
        #EAB308 60deg 120deg,
        #F59E0B 120deg 180deg,
        #EF4444 180deg 240deg,
        #A855F7 240deg 360deg
    )`,
};

const markerStyle = computed(() => ({
    transform: `rotate(${angle.value}deg) translate(0, -110px) rotate(-${angle.value}deg)`,
    borderColor: props.color,
}));
</script>

<template>
    <div class="relative flex h-64 w-64 items-center justify-center rounded-full" :style="gaugeStyle">
        <div class="absolute inset-3 rounded-full bg-airzen-card"></div>
        <div class="airzen-gauge-marker absolute left-1/2 top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow" :style="markerStyle"></div>
        <div class="relative flex flex-col items-center font-heading">
            <span class="text-5xl font-bold tabular-nums" :style="{ color: props.color }">{{ Math.round(displayedAqi) }}</span>
            <span class="text-sm font-medium text-airzen-muted">AQI</span>
        </div>
    </div>
</template>

<style scoped>
.airzen-gauge-marker {
    transition: border-color 400ms ease;
}
</style>
