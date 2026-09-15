<script setup lang="ts">
import { computed } from 'vue';

interface DayValue {
    label: string;
    date: string;
    average_value: number | null;
    is_today: boolean;
}

const props = defineProps<{
    days: DayValue[];
    color: string;
}>();

// Scaled by this dataset's own min/max, not a fixed health-band scale - this
// chart is used for raw sensor values (temperature, humidity, ...) that have
// no such scale.
const range = computed(() => {
    const values = props.days.map((d) => d.average_value).filter((v): v is number => v !== null);
    if (values.length === 0) return { min: 0, max: 1 };
    return { min: Math.min(...values, 0), max: Math.max(...values) };
});

function heightScale(value: number | null): number {
    if (value === null) return 1;
    const { min, max } = range.value;
    const span = max - min || 1;
    return Math.max(0.06, (value - min) / span);
}
</script>

<template>
    <div class="flex h-40 items-end gap-2">
        <div v-for="day in days" :key="day.date" class="flex flex-1 flex-col items-center gap-2">
            <div class="flex h-32 w-full items-end justify-center">
                <div
                    v-if="day.average_value !== null"
                    class="airzen-generic-bar h-full w-full max-w-8 origin-bottom rounded-t-md"
                    :style="{ transform: `scaleY(${heightScale(day.average_value)})`, backgroundColor: color }"
                    :title="`${day.label}: ${day.average_value}`"
                ></div>
                <div
                    v-else
                    class="h-full w-full max-w-8 origin-bottom scale-y-[0.1] rounded-t-md border-2 border-dashed border-[#D7E8DD]"
                    title="No readings recorded this day"
                ></div>
            </div>
            <span class="text-[11px] font-medium" :class="day.is_today ? 'text-[#1D352D]' : 'text-[#6B8577]'">
                {{ day.is_today ? 'Today' : day.label }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.airzen-generic-bar {
    transition: transform 500ms cubic-bezier(0.23, 1, 0.32, 1);
}
</style>
