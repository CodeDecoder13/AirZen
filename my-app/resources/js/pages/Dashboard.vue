<script setup lang="ts">
import AqiGauge from '@/components/AqiGauge.vue';
import AqiSparkline from '@/components/AqiSparkline.vue';
import WeeklyAqiBars from '@/components/WeeklyAqiBars.vue';
import { useLatestReading, type ReadingSnapshot } from '@/composables/useLatestReading';
import { useReadingHistory, type ReadingHistory } from '@/composables/useReadingHistory';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Droplets, Flame, Leaf, ShieldCheck, Thermometer, Wind, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    initialSnapshot: ReadingSnapshot;
    initialHistory: ReadingHistory;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const page = usePage<SharedData>();
const { snapshot } = useLatestReading(props.initialSnapshot);
const { history } = useReadingHistory(props.initialHistory);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

const hasAnyReading = computed(() => Object.values(snapshot.value.readings).some((value) => value !== null));

const STATUS_DESCRIPTIONS: Record<string, string> = {
    Good: 'Air quality is comfortable and healthy. No immediate action needed.',
    Normal: 'Air quality is acceptable. Light ventilation is still a good idea.',
    'Unhealthy for Sensitive Groups': 'Sensitive individuals may notice mild effects. Consider the tips below.',
    'Unhealthy for All Groups': 'Air quality is degraded for everyone. Follow the recommendations below.',
    'Very Unhealthy': 'Air quality is poor. Take action to improve ventilation and filtration now.',
};

const statusDescription = computed(() => STATUS_DESCRIPTIONS[snapshot.value.status] ?? '');

function formatUpdatedAt(iso: string | null): string {
    if (!iso) return 'No readings yet';

    const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
    if (seconds < 60) return 'Updated just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `Updated ${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    return `Updated ${hours}h ago`;
}

const RECOMMENDATION_ICONS: LucideIcon[] = [Leaf, Wind, ShieldCheck];

interface Metric {
    key: string;
    label: string;
    icon: LucideIcon;
    value: number | null;
    unit: string;
    decimals: number;
    description: string;
    badge?: { text: string; color: string };
}

const metrics = computed<Metric[]>(() => [
    {
        key: 'particulate_matter',
        label: 'PM2.5',
        icon: Wind,
        value: snapshot.value.readings.particulate_matter,
        unit: ' µg/m³',
        decimals: 1,
        description: 'Fine particulate matter — the only metric calibrated to real units, and what drives the AQI score above.',
        badge: { text: snapshot.value.status, color: snapshot.value.color },
    },
    {
        key: 'temperature',
        label: 'Temperature',
        icon: Thermometer,
        value: snapshot.value.readings.temperature,
        unit: ' °C',
        decimals: 1,
        description: 'Ambient room temperature.',
    },
    {
        key: 'humidity',
        label: 'Humidity',
        icon: Droplets,
        value: snapshot.value.readings.humidity,
        unit: '%',
        decimals: 1,
        description: 'Relative humidity in the room.',
    },
    {
        key: 'nitrogen',
        label: 'Nitrogen',
        icon: Flame,
        value: snapshot.value.readings.nitrogen,
        unit: '',
        decimals: 1,
        description: 'Raw NOx sensor signal (0–3V scale) — not yet ppm-calibrated.',
    },
    {
        key: 'co',
        label: 'C0',
        icon: Flame,
        value: snapshot.value.readings.co,
        unit: '',
        decimals: 2,
        description: 'Raw CO sensor signal (0–3V scale) — not yet ppm-calibrated.',
    },
]);

const scaleTicks = [0, 50, 100, 150, 200, 300];
const indexFillScale = computed(() => Math.min(snapshot.value.aqi, 300) / 300);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 bg-airzen-bg p-4 md:p-6">
            <div class="airzen-section flex flex-wrap items-center justify-between gap-3" style="animation-delay: 0ms">
                <div>
                    <h1 class="font-heading text-2xl font-semibold text-[#0B2A18]">{{ greeting }}, {{ page.props.auth.user.name }}</h1>
                    <p class="text-sm text-airzen-muted">Your indoor air is being monitored.</p>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-airzen-card px-3 py-1.5 text-xs font-medium text-airzen-muted shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
                    <span class="airzen-live-dot h-2 w-2 rounded-full bg-airzen-green"></span>
                    {{ formatUpdatedAt(snapshot.updated_at) }}
                </div>
            </div>

            <div class="airzen-section grid gap-6 lg:grid-cols-5" style="animation-delay: 60ms">
                <div
                    class="flex flex-col items-center gap-4 rounded-[22px] bg-airzen-card p-8 text-center shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)] lg:col-span-2"
                >
                    <AqiGauge v-if="hasAnyReading" :aqi="snapshot.aqi" :color="snapshot.color" />
                    <p v-else class="py-16 text-sm text-airzen-muted">Waiting for the first sensor reading&hellip;</p>
                    <div v-if="hasAnyReading" class="flex flex-col items-center gap-2">
                        <div class="rounded-full px-4 py-1 text-sm font-medium" :style="{ backgroundColor: snapshot.color + '22', color: snapshot.color }">
                            {{ snapshot.status }}
                        </div>
                        <p class="max-w-[26ch] text-sm text-airzen-muted">{{ statusDescription }}</p>
                    </div>
                </div>

                <div class="rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)] lg:col-span-3">
                    <h2 class="font-heading text-lg font-semibold text-[#0B2A18]">Recommendations</h2>
                    <ul class="mt-4 divide-y divide-[#EEF3EF]">
                        <li v-for="(item, index) in snapshot.recommendations" :key="index" class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-airzen-green-light text-airzen-green-dark">
                                <component :is="RECOMMENDATION_ICONS[index % RECOMMENDATION_ICONS.length]" :size="18" :stroke-width="2" />
                            </span>
                            <p class="pt-1.5 text-sm leading-relaxed text-[#28382F]">{{ item }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="airzen-section rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]" style="animation-delay: 120ms">
                <h2 class="font-heading text-lg font-semibold text-[#0B2A18]">Breakdown</h2>

                <div class="mt-5">
                    <div class="flex items-baseline justify-between text-xs text-airzen-muted">
                        <span>Index score</span>
                        <span class="font-heading text-2xl font-semibold" :style="{ color: snapshot.color }">{{ Math.round(snapshot.aqi) }}</span>
                    </div>
                    <div class="relative mt-2 h-2.5 overflow-hidden rounded-full bg-[#EEF3EF]">
                        <div
                            class="airzen-index-fill absolute inset-y-0 left-0 w-full origin-left rounded-full"
                            :style="{ transform: `scaleX(${indexFillScale})`, backgroundColor: snapshot.color }"
                        ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-[11px] text-airzen-muted">
                        <span v-for="tick in scaleTicks" :key="tick">{{ tick }}</span>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-medium uppercase tracking-[-0.02em] text-airzen-muted">PM2.5 &middot; recent trend</p>
                    <AqiSparkline :points="history.trend" :color="snapshot.color" />
                </div>

                <ul class="mt-2 divide-y divide-[#EEF3EF]">
                    <li v-for="metric in metrics" :key="metric.key" class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F1F8F4] text-airzen-green-dark">
                            <component :is="metric.icon" :size="17" :stroke-width="2" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-[#28382F]">{{ metric.label }}</span>
                                <span
                                    v-if="metric.badge"
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :style="{ backgroundColor: metric.badge.color + '22', color: metric.badge.color }"
                                >
                                    {{ metric.badge.text }}
                                </span>
                            </div>
                            <p class="truncate text-xs text-airzen-muted">{{ metric.description }}</p>
                        </div>
                        <span class="shrink-0 font-heading text-base font-semibold tabular-nums text-[#28382F]">
                            {{ metric.value === null ? '–' : `${metric.value.toFixed(metric.decimals)}${metric.unit}` }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="airzen-section rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]" style="animation-delay: 180ms">
                <h2 class="font-heading text-lg font-semibold text-[#0B2A18]">This week</h2>
                <p class="text-xs text-airzen-muted">Daily average AQI, from PM2.5 readings.</p>
                <div class="mt-5">
                    <WeeklyAqiBars :days="history.weekly" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.airzen-live-dot {
    animation: airzen-live-pulse 2s ease-in-out infinite;
}

.airzen-index-fill {
    transition: transform 500ms cubic-bezier(0.23, 1, 0.32, 1);
}

.airzen-section {
    animation: airzen-section-in 450ms cubic-bezier(0.23, 1, 0.32, 1) both;
}

@keyframes airzen-section-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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

@media (prefers-reduced-motion: reduce) {
    .airzen-section {
        animation: none;
    }
}
</style>
