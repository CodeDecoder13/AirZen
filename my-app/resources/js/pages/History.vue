<script setup lang="ts">
import AqiSparkline from '@/components/AqiSparkline.vue';
import BandDistributionChart from '@/components/BandDistributionChart.vue';
import GenericBarChart from '@/components/GenericBarChart.vue';
import GenericPieChart from '@/components/GenericPieChart.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed } from 'vue';

interface Reading {
    id: number;
    type: string;
    value: number;
    recorded_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DayValue {
    label: string;
    date: string;
    average_value: number | null;
    is_today: boolean;
}

type ChartType = 'line' | 'bar' | 'pie';

interface Props {
    days: number;
    filters: { type: string; from: string; to: string };
    trend: { value: number; recorded_at: string }[];
    bandDistribution: Record<string, number>;
    readings: { data: Reading[]; links: PaginationLink[] };
    playground: { metric: string; chartType: ChartType; daily: DayValue[]; buckets: Record<string, number> };
}

const props = defineProps<Props>();

const SENSOR_TYPES = ['TEMPERATURE', 'HUMIDITY', 'NITROGEN', 'C0', 'ParticulateMatter'];

const METRIC_META: Record<string, { label: string; color: string }> = {
    ParticulateMatter: { label: 'PM2.5', color: '#2A8362' },
    TEMPERATURE: { label: 'Temperature', color: '#F59E0B' },
    HUMIDITY: { label: 'Humidity', color: '#3B82F6' },
    NITROGEN: { label: 'Nitrogen', color: '#A855F7' },
    C0: { label: 'CO', color: '#EF4444' },
};

const BUCKET_ORDER = ['Low', 'Mid', 'High'];
const BUCKET_COLORS: Record<string, string> = { Low: '#22C55E', Mid: '#EAB308', High: '#EF4444' };

const breadcrumbs: BreadcrumbItem[] = [{ title: 'History', href: '/history' }];

const playgroundPoints = computed(() =>
    props.playground.daily
        .filter((day) => day.average_value !== null)
        .map((day) => ({ value: day.average_value as number, recorded_at: day.date })),
);

const playgroundColor = computed(() => METRIC_META[props.playground.metric]?.color ?? '#2A8362');

function visit(overrides: Partial<{ days: number; type: string; metric: string; chartType: ChartType }>) {
    router.get(
        route('history.index'),
        {
            days: props.days,
            type: props.filters.type,
            metric: props.playground.metric,
            chartType: props.playground.chartType,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function goToPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function exportUrl(format: 'csv' | 'pdf'): string {
    const params: Record<string, string> = {};
    if (props.filters.type) params.type = props.filters.type;
    if (props.filters.from) params.from = props.filters.from;
    if (props.filters.to) params.to = props.filters.to;

    return route(format === 'csv' ? 'history.export.csv' : 'history.export.pdf', params);
}
</script>

<template>
    <Head title="History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="az2-display text-2xl text-[#1D352D]">History</h1>
                    <p class="text-sm text-[#6B8577]">Every indoor air quality reading this device has ever sent.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="exportUrl('csv')"
                        class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-[#1D352D] shadow-[0_1px_2px_rgba(20,35,25,0.04)] hover:text-[#2A8362]"
                    >
                        <Download :size="13" /> CSV
                    </a>
                    <a
                        :href="exportUrl('pdf')"
                        class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-[#1D352D] shadow-[0_1px_2px_rgba(20,35,25,0.04)] hover:text-[#2A8362]"
                    >
                        <Download :size="13" /> PDF
                    </a>
                    <div class="flex gap-1.5 rounded-full bg-white p-1 shadow-[0_1px_2px_rgba(20,35,25,0.04)]">
                        <button
                            v-for="range in [7, 30, 90]"
                            :key="range"
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-bold"
                            :class="days === range ? 'bg-[#2A8362] text-white' : 'text-[#6B8577] hover:text-[#1D352D]'"
                            @click="visit({ days: range })"
                        >
                            {{ range }}d
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                    <h2 class="az2-display text-lg text-[#1D352D]">PM2.5 daily average</h2>
                    <p class="text-xs text-[#6B8577]">Last {{ days }} days.</p>
                    <div class="mt-4">
                        <AqiSparkline :points="trend" color="#2A8362" />
                    </div>
                </div>

                <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                    <h2 class="az2-display text-lg text-[#1D352D]">Days by air quality band</h2>
                    <p class="text-xs text-[#6B8577]">Last {{ days }} days.</p>
                    <div class="mt-4">
                        <BandDistributionChart :distribution="bandDistribution" />
                    </div>
                </div>
            </div>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="az2-display text-lg text-[#1D352D]">Playground</h2>
                        <p class="text-xs text-[#6B8577]">Pick a sensor and a chart type to explore its {{ days }}-day trend.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            :value="playground.metric"
                            class="rounded-[10px] border border-[#E4EAE0] bg-[#F7F8F1] px-3 py-1.5 text-sm text-[#1D352D]"
                            @change="visit({ metric: ($event.target as HTMLSelectElement).value })"
                        >
                            <option v-for="type in SENSOR_TYPES" :key="type" :value="type">{{ METRIC_META[type]?.label ?? type }}</option>
                        </select>
                        <div class="flex gap-1.5 rounded-full bg-[#F7F8F1] p-1">
                            <button
                                v-for="chart in (['line', 'bar', 'pie'] as ChartType[])"
                                :key="chart"
                                type="button"
                                class="rounded-full px-3 py-1.5 text-xs font-bold capitalize"
                                :class="playground.chartType === chart ? 'bg-[#2A8362] text-white' : 'text-[#6B8577] hover:text-[#1D352D]'"
                                @click="visit({ chartType: chart })"
                            >
                                {{ chart }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <AqiSparkline v-if="playground.chartType === 'line'" :points="playgroundPoints" :color="playgroundColor" />
                    <GenericBarChart v-else-if="playground.chartType === 'bar'" :days="playground.daily" :color="playgroundColor" />
                    <GenericPieChart
                        v-else
                        :data="playground.buckets"
                        :colors="BUCKET_COLORS"
                        :order="BUCKET_ORDER"
                        :center-label="METRIC_META[playground.metric]?.label"
                    />
                </div>
            </div>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="az2-display text-lg text-[#1D352D]">Reading log</h2>
                    <select
                        :value="filters.type"
                        class="rounded-[10px] border border-[#E4EAE0] bg-[#F7F8F1] px-3 py-1.5 text-sm text-[#1D352D]"
                        @change="visit({ type: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">All types</option>
                        <option v-for="type in SENSOR_TYPES" :key="type" :value="type">{{ type }}</option>
                    </select>
                </div>

                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#EEF1E9] text-[11px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">
                            <th class="pb-2">Type</th>
                            <th class="pb-2">Value</th>
                            <th class="pb-2">Recorded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF1E9]">
                        <tr v-for="reading in readings.data" :key="reading.id">
                            <td class="py-2.5 text-[#1D352D]">{{ reading.type }}</td>
                            <td class="py-2.5 tabular-nums text-[#1D352D]">{{ reading.value }}</td>
                            <td class="py-2.5 text-[#6B8577]">{{ new Date(reading.recorded_at).toLocaleString() }}</td>
                        </tr>
                        <tr v-if="readings.data.length === 0">
                            <td colspan="3" class="py-6 text-center text-[#6B8577]">No readings in this range.</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 flex flex-wrap justify-center gap-1">
                    <button
                        v-for="link in readings.links"
                        :key="link.label"
                        type="button"
                        class="rounded-full px-3 py-1 text-xs"
                        :class="link.active ? 'bg-[#2A8362] text-white' : 'text-[#6B8577] hover:text-[#1D352D]'"
                        :disabled="!link.url"
                        v-html="link.label"
                        @click="goToPage(link.url)"
                    ></button>
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
</style>
