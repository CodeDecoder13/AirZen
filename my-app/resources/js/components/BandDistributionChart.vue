<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
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

const total = computed(() => Object.values(props.distribution).reduce((sum, count) => sum + count, 0));

const segments = computed(() => {
    let cursor = 0;
    return BAND_ORDER.filter((band) => props.distribution[band] > 0).map((band) => {
        const count = props.distribution[band];
        const start = (cursor / total.value) * 360;
        cursor += count;
        const end = (cursor / total.value) * 360;
        return { band, count, color: BAND_COLORS[band], start, end };
    });
});

const gradient = computed(() => {
    if (total.value === 0) return '#EEF1E9';
    return `conic-gradient(${segments.value.map((s) => `${s.color} ${s.start}deg ${s.end}deg`).join(', ')})`;
});
</script>

<template>
    <div class="flex items-center gap-6">
        <div class="relative h-32 w-32 shrink-0 rounded-full" :style="{ background: gradient }">
            <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white text-center">
                <span class="az2-display text-xl text-[#1D352D]">{{ total }}</span>
                <span class="text-[9px] uppercase tracking-[0.08em] text-[#6B8577]">days</span>
            </div>
        </div>
        <ul class="flex flex-1 flex-col gap-2 text-sm">
            <li v-for="segment in segments" :key="segment.band" class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 text-[#1D352D]">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }"></span>
                    {{ segment.band }}
                </span>
                <span class="font-medium text-[#6B8577]">{{ segment.count }}</span>
            </li>
            <li v-if="total === 0" class="text-[#6B8577]">No data in this range yet.</li>
        </ul>
    </div>
</template>

<style scoped>
.az2-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
    letter-spacing: -0.02em;
    font-weight: 500;
}
</style>
