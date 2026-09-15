<script setup lang="ts">
import MiniSparkline from '@/components/MiniSparkline.vue';
import { useLatestReading, type ReadingSnapshot, type SensorReadings } from '@/composables/useLatestReading';
import { formatUpdatedAt, RECOMMENDATION_ICONS, STATUS_DESCRIPTIONS, STATUS_HEADLINES } from '@/lib/airQualityCopy';
import { Atom, Droplets, Flame, Thermometer, Wind, type LucideIcon } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{
    initialSnapshot: ReadingSnapshot;
}>();

const { snapshot } = useLatestReading(props.initialSnapshot, 8000, '/api/public/readings/latest');

const hasAnyReading = computed(() => Object.values(snapshot.value.readings).some((value) => value !== null));
const statusHeadline = computed(() => STATUS_HEADLINES[snapshot.value.status] ?? { line1: 'Your home', line2: 'is being monitored.' });
const statusDescription = computed(() => STATUS_DESCRIPTIONS[snapshot.value.status] ?? '');

// Rolling client-side buffer of real polled values, not fabricated data — the
// line simply has nothing to draw until the device has sent a couple of
// readings while this page has been open.
const MAX_POINTS = 20;
const series = reactive<Record<keyof SensorReadings, number[]>>({
    particulate_matter: [],
    temperature: [],
    humidity: [],
    nitrogen: [],
    co: [],
});

watch(
    () => snapshot.value.readings,
    (readings) => {
        (Object.keys(series) as (keyof SensorReadings)[]).forEach((key) => {
            const value = readings[key];
            if (value === null) return;

            series[key].push(value);
            if (series[key].length > MAX_POINTS) series[key].shift();
        });
    },
    { immediate: true },
);

interface Metric {
    key: keyof SensorReadings;
    label: string;
    icon: LucideIcon;
    value: number | null;
    unit: string;
    decimals: number;
    wide?: boolean;
}

const metrics = computed<Metric[]>(() => [
    { key: 'particulate_matter', label: 'PM2.5', icon: Wind, value: snapshot.value.readings.particulate_matter, unit: ' µg/m³', decimals: 1 },
    { key: 'temperature', label: 'Temperature', icon: Thermometer, value: snapshot.value.readings.temperature, unit: '°C', decimals: 1 },
    { key: 'humidity', label: 'Humidity', icon: Droplets, value: snapshot.value.readings.humidity, unit: '%', decimals: 1 },
    { key: 'co', label: 'CO', icon: Flame, value: snapshot.value.readings.co, unit: '', decimals: 2 },
    { key: 'nitrogen', label: 'Nitrogen', icon: Atom, value: snapshot.value.readings.nitrogen, unit: '', decimals: 1, wide: true },
]);

function formatValue(metric: Metric): string {
    return metric.value === null ? '–' : `${metric.value.toFixed(metric.decimals)}${metric.unit}`;
}
</script>

<template>
    <div class="az2-sans overflow-hidden rounded-[28px] bg-white shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
        <div class="grid gap-5 p-5 md:grid-cols-[1.3fr_1fr] md:p-6">
            <div class="relative overflow-hidden rounded-[22px] bg-[#2A8362] p-6 text-white sm:p-8">
                <div class="airzen-air-ring airzen-air-ring-1 absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10"></div>
                <div class="airzen-air-ring airzen-air-ring-2 absolute -right-2 top-8 h-24 w-24 rounded-full border border-white/10"></div>

                <span class="airzen-air-particle airzen-air-particle-1 absolute right-16 top-20 h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
                <span class="airzen-air-particle airzen-air-particle-2 absolute right-28 top-32 h-1 w-1 rounded-full bg-[#C6DF58]"></span>
                <span class="airzen-air-particle airzen-air-particle-3 absolute right-10 top-40 h-1.5 w-1.5 rounded-full bg-white/60"></span>
                <span class="airzen-air-particle airzen-air-particle-4 absolute right-36 top-16 h-1 w-1 rounded-full bg-white/60"></span>

                <template v-if="hasAnyReading">
                    <p class="relative flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.1em] text-white/70">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
                        Air quality now
                    </p>
                    <h3 class="az2-display relative mt-2 text-3xl leading-[1.05] sm:text-4xl">
                        {{ statusHeadline.line1 }}<br />
                        <span class="text-[#C6DF58]">{{ statusHeadline.line2 }}</span>
                    </h3>
                    <p class="relative mt-3 max-w-[32ch] text-sm text-white/70">{{ statusDescription }}</p>

                    <div class="relative mt-6 flex flex-wrap items-end justify-between gap-4">
                        <div class="flex items-baseline gap-2">
                            <span class="az2-display text-6xl text-[#C6DF58]">{{ Math.round(snapshot.aqi) }}</span>
                            <span class="pb-1">
                                <span class="block text-base font-bold">{{ snapshot.status }}</span>
                                <span class="block text-xs text-white/60">out of 300 &middot; PM2.5-driven</span>
                            </span>
                        </div>
                        <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                            <span class="airzen-live-dot h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
                            {{ formatUpdatedAt(snapshot.updated_at) }}
                        </span>
                    </div>
                </template>
                <p v-else class="relative py-16 text-center text-sm text-white/80">Waiting for the first sensor reading&hellip;</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div
                    v-for="metric in metrics"
                    :key="metric.key"
                    class="rounded-[18px] bg-[#F7F8F1] p-3.5"
                    :class="metric.wide ? 'col-span-2 flex items-center gap-4' : 'flex flex-col justify-between'"
                >
                    <template v-if="metric.wide">
                        <div class="flex flex-1 items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-[#2A8362]">
                                <component :is="metric.icon" :size="14" />
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">{{ metric.label }}</span>
                        </div>
                        <MiniSparkline :points="series[metric.key]" color="#2A8362" class="w-20" />
                        <p class="az2-display shrink-0 text-2xl text-[#1D352D]">{{ formatValue(metric) }}</p>
                    </template>
                    <template v-else>
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-[#2A8362]">
                                <component :is="metric.icon" :size="14" />
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">{{ metric.label }}</span>
                        </div>
                        <MiniSparkline :points="series[metric.key]" color="#2A8362" />
                        <p class="az2-display text-2xl text-[#1D352D]">{{ formatValue(metric) }}</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="border-t border-[#EEF1E9] p-5 md:p-6">
            <h4 class="az2-display text-xl text-[#1D352D]">A little care, at the right time.</h4>
            <p class="mt-1 text-sm text-[#6B8577]">Suggestions, never alarms.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                <li v-for="(item, index) in snapshot.recommendations" :key="index" class="flex items-start gap-2.5 rounded-[16px] bg-[#F7F8F1] p-3.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2A8362]/10 text-[#2A8362]">
                        <component :is="RECOMMENDATION_ICONS[index % RECOMMENDATION_ICONS.length]" :size="14" />
                    </span>
                    <p class="text-xs leading-relaxed text-[#3C4A41]">{{ item }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
.az2-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
    letter-spacing: -0.02em;
    font-weight: 500;
}
.az2-sans {
    font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}

.airzen-live-dot {
    animation: airzen-live-pulse 2s ease-in-out infinite;
}

.airzen-air-ring {
    animation: airzen-air-ring-pulse 4s ease-in-out infinite;
    transform-origin: center;
}
.airzen-air-ring-2 {
    animation-delay: 1.3s;
}

@keyframes airzen-air-ring-pulse {
    0%,
    100% {
        opacity: 0.5;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.06);
    }
}

@keyframes airzen-live-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.35;
    }
}

.airzen-air-particle {
    animation: airzen-air-particle-drift 6s ease-in infinite;
    opacity: 0;
}
.airzen-air-particle-2 {
    animation-delay: 1.5s;
}
.airzen-air-particle-3 {
    animation-delay: 3s;
}
.airzen-air-particle-4 {
    animation-delay: 4.5s;
}

@keyframes airzen-air-particle-drift {
    0% {
        transform: translateY(0);
        opacity: 0;
    }
    15% {
        opacity: 0.9;
    }
    85% {
        opacity: 0.4;
    }
    100% {
        transform: translateY(-36px);
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .airzen-air-ring,
    .airzen-air-particle {
        animation: none;
    }
    .airzen-air-particle {
        opacity: 0.5;
    }
}
</style>
