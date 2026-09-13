<script setup lang="ts">
import type { WeeklyDay } from '@/composables/useReadingHistory';
import { computed } from 'vue';

const props = defineProps<{
    days: WeeklyDay[];
}>();

const BANDS = [
    { max: 50, color: '#22C55E' },
    { max: 100, color: '#EAB308' },
    { max: 150, color: '#F59E0B' },
    { max: 200, color: '#EF4444' },
    { max: 300, color: '#A855F7' },
];

function colorFor(aqi: number): string {
    return BANDS.find((band) => aqi <= band.max)?.color ?? BANDS.at(-1)!.color;
}

const scaleMax = computed(() => {
    const values = props.days.map((d) => d.average_aqi).filter((v): v is number => v !== null);
    return Math.max(50, ...values);
});

function heightScale(aqi: number | null): number {
    if (aqi === null) return 1;
    return Math.max(0.06, aqi / scaleMax.value);
}
</script>

<template>
    <div class="flex h-40 items-end gap-3">
        <div v-for="day in days" :key="day.date" class="flex flex-1 flex-col items-center gap-2">
            <div class="flex h-32 w-full items-end justify-center">
                <div
                    v-if="day.average_aqi !== null"
                    class="airzen-bar h-full w-full max-w-8 origin-bottom rounded-t-md"
                    :style="{ transform: `scaleY(${heightScale(day.average_aqi)})`, backgroundColor: colorFor(day.average_aqi) }"
                    :title="`${day.label}: AQI ${Math.round(day.average_aqi)}`"
                ></div>
                <div
                    v-else
                    class="h-full w-full max-w-8 origin-bottom scale-y-[0.1] rounded-t-md border-2 border-dashed border-[#D7E8DD]"
                    title="No readings recorded this day"
                ></div>
            </div>
            <span class="text-xs font-medium" :class="day.is_today ? 'text-airzen-green-dark' : 'text-airzen-muted'">
                {{ day.is_today ? 'Today' : day.label }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.airzen-bar {
    transition: transform 500ms cubic-bezier(0.23, 1, 0.32, 1);
}
</style>
