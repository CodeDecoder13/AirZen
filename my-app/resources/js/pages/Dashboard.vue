<script setup lang="ts">
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

const STATUS_HEADLINES: Record<string, { line1: string; line2: string }> = {
    Good: { line1: 'Your home feels', line2: 'clear.' },
    Normal: { line1: 'Your home feels', line2: 'steady.' },
    'Unhealthy for Sensitive Groups': { line1: 'Your home needs', line2: 'a little care.' },
    'Unhealthy for All Groups': { line1: 'Your home needs', line2: 'attention.' },
    'Very Unhealthy': { line1: 'Your home needs', line2: 'action now.' },
};

const statusHeadline = computed(() => STATUS_HEADLINES[snapshot.value.status] ?? { line1: 'Your home', line2: 'is being monitored.' });

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
    <Head title="Dashboard">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600|dm-sans:400,500,700" rel="stylesheet" />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div class="airzen-section flex flex-wrap items-center justify-between gap-3" style="animation-delay: 0ms">
                <div>
                    <h1 class="az2-display text-2xl text-[#1D352D]">{{ greeting }}, {{ page.props.auth.user.name }}</h1>
                    <p class="text-sm text-[#6B8577]">Your indoor air is being monitored.</p>
                </div>
                <div
                    class="flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-[#6B8577] shadow-[0_1px_2px_rgba(20,35,25,0.04),0_16px_32px_-20px_rgba(20,35,25,0.2)]"
                >
                    <span class="airzen-live-dot h-2 w-2 rounded-full bg-[#2A8362]"></span>
                    {{ formatUpdatedAt(snapshot.updated_at) }}
                </div>
            </div>

            <div class="airzen-section grid gap-6 lg:grid-cols-5" style="animation-delay: 60ms">
                <div class="relative overflow-hidden rounded-[22px] bg-[#2A8362] p-8 text-white lg:col-span-2">
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
                        <h3 class="az2-display relative mt-2 text-3xl leading-[1.05]">
                            {{ statusHeadline.line1 }}<br />
                            <span class="text-[#C6DF58]">{{ statusHeadline.line2 }}</span>
                        </h3>
                        <p class="relative mt-3 max-w-[30ch] text-sm text-white/70">{{ statusDescription }}</p>

                        <div class="relative mt-6 flex flex-wrap items-end justify-between gap-4">
                            <div class="flex items-baseline gap-2">
                                <span class="az2-display text-6xl text-[#C6DF58]">{{ Math.round(snapshot.aqi) }}</span>
                                <span class="pb-1">
                                    <span class="block text-base font-bold">{{ snapshot.status }}</span>
                                    <span class="block text-xs text-white/60">out of 300 &middot; PM2.5-driven</span>
                                </span>
                            </div>
                            <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                                {{ formatUpdatedAt(snapshot.updated_at) }}
                            </span>
                        </div>
                    </template>
                    <p v-else class="relative py-16 text-center text-sm text-white/80">Waiting for the first sensor reading&hellip;</p>
                </div>

                <div class="rounded-[22px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)] lg:col-span-3">
                    <h2 class="az2-display text-xl text-[#1D352D]">A little care, at the right time.</h2>
                    <p class="mt-1 text-sm text-[#6B8577]">Suggestions, never alarms.</p>
                    <ul class="mt-4 divide-y divide-[#EEF1E9]">
                        <li v-for="(item, index) in snapshot.recommendations" :key="index" class="flex items-start gap-3 py-3 first:pt-3 last:pb-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A8362]/10 text-[#2A8362]">
                                <component :is="RECOMMENDATION_ICONS[index % RECOMMENDATION_ICONS.length]" :size="18" :stroke-width="2" />
                            </span>
                            <p class="pt-1.5 text-sm leading-relaxed text-[#3C4A41]">{{ item }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="airzen-section rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]"
                style="animation-delay: 120ms"
            >
                <h2 class="az2-display text-xl text-[#1D352D]">Breakdown</h2>

                <div class="mt-5">
                    <div class="flex items-baseline justify-between text-xs text-[#6B8577]">
                        <span>Index score</span>
                        <span class="az2-display text-2xl" :style="{ color: snapshot.color }">{{ Math.round(snapshot.aqi) }}</span>
                    </div>
                    <div class="relative mt-2 h-2.5 overflow-hidden rounded-full bg-[#EEF1E9]">
                        <div
                            class="airzen-index-fill absolute inset-y-0 left-0 w-full origin-left rounded-full"
                            :style="{ transform: `scaleX(${indexFillScale})`, backgroundColor: snapshot.color }"
                        ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-[11px] text-[#6B8577]">
                        <span v-for="tick in scaleTicks" :key="tick">{{ tick }}</span>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-[#6B8577]">PM2.5 &middot; recent trend</p>
                    <AqiSparkline :points="history.trend" :color="snapshot.color" />
                </div>

                <ul class="mt-2 divide-y divide-[#EEF1E9]">
                    <li v-for="metric in metrics" :key="metric.key" class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F7F8F1] text-[#2A8362]">
                            <component :is="metric.icon" :size="17" :stroke-width="2" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-[#1D352D]">{{ metric.label }}</span>
                                <span
                                    v-if="metric.badge"
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :style="{ backgroundColor: metric.badge.color + '22', color: metric.badge.color }"
                                >
                                    {{ metric.badge.text }}
                                </span>
                            </div>
                            <p class="truncate text-xs text-[#6B8577]">{{ metric.description }}</p>
                        </div>
                        <span class="az2-display shrink-0 text-base tabular-nums text-[#1D352D]">
                            {{ metric.value === null ? '–' : `${metric.value.toFixed(metric.decimals)}${metric.unit}` }}
                        </span>
                    </li>
                </ul>
            </div>

            <div
                class="airzen-section rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]"
                style="animation-delay: 180ms"
            >
                <h2 class="az2-display text-xl text-[#1D352D]">This week</h2>
                <p class="text-xs text-[#6B8577]">Daily average AQI, from PM2.5 readings.</p>
                <div class="mt-5">
                    <WeeklyAqiBars :days="history.weekly" />
                </div>
            </div>
        </div>
    </AppLayout>
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

.airzen-index-fill {
    transition: transform 500ms cubic-bezier(0.23, 1, 0.32, 1);
}

.airzen-section {
    animation: airzen-section-in 450ms cubic-bezier(0.23, 1, 0.32, 1) both;
}

.airzen-air-ring {
    animation: airzen-air-ring-pulse 4s ease-in-out infinite;
    transform-origin: center;
}
.airzen-air-ring-2 {
    animation-delay: 1.3s;
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
    .airzen-section,
    .airzen-air-ring,
    .airzen-air-particle {
        animation: none;
    }
    .airzen-air-particle {
        opacity: 0.5;
    }
}
</style>
