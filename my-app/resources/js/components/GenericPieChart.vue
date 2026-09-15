<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    data: Record<string, number>;
    colors: Record<string, string>;
    order: string[];
    centerLabel?: string;
}>();

const total = computed(() => Object.values(props.data).reduce((sum, count) => sum + count, 0));

const segments = computed(() => {
    let cursor = 0;
    return props.order
        .filter((key) => (props.data[key] ?? 0) > 0)
        .map((key) => {
            const count = props.data[key];
            const start = (cursor / total.value) * 360;
            cursor += count;
            const end = (cursor / total.value) * 360;
            return { key, count, color: props.colors[key] ?? '#6B8577', start, end };
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
                <span class="text-[9px] uppercase tracking-[0.08em] text-[#6B8577]">{{ centerLabel ?? 'days' }}</span>
            </div>
        </div>
        <ul class="flex flex-1 flex-col gap-2 text-sm">
            <li v-for="segment in segments" :key="segment.key" class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 text-[#1D352D]">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }"></span>
                    {{ segment.key }}
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
