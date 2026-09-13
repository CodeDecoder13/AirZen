<script setup lang="ts">
import AqiGauge from '@/components/AqiGauge.vue';
import { useLatestReading, type ReadingSnapshot } from '@/composables/useLatestReading';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    initialSnapshot: ReadingSnapshot;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const { snapshot } = useLatestReading(props.initialSnapshot);

const hasAnyReading = computed(() => Object.values(snapshot.value.readings).some((value) => value !== null));

function formatValue(value: number | null, suffix: string, decimals = 1): string {
    return value === null ? '--' : `${value.toFixed(decimals)}${suffix}`;
}

const sensorStrip = computed(() => [
    { label: 'Temperature', value: formatValue(snapshot.value.readings.temperature, ' °C') },
    { label: 'Humidity', value: formatValue(snapshot.value.readings.humidity, ' %') },
    { label: 'Nitrogen', value: formatValue(snapshot.value.readings.nitrogen, '') },
    { label: 'C0', value: formatValue(snapshot.value.readings.co, ' ppm', 2) },
    { label: 'PM2.5', value: formatValue(snapshot.value.readings.particulate_matter, ' µg/m³') },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl bg-airzen-bg p-4">
            <div class="grid gap-6 md:grid-cols-2">
                <div
                    class="flex flex-col items-center gap-4 rounded-[22px] bg-airzen-card p-8 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]"
                >
                    <AqiGauge v-if="hasAnyReading" :aqi="snapshot.aqi" :color="snapshot.color" />
                    <p v-else class="text-sm text-airzen-muted">Waiting for the first sensor reading&hellip;</p>
                    <div class="rounded-full px-4 py-1 text-sm font-medium" :style="{ backgroundColor: snapshot.color + '22', color: snapshot.color }">
                        {{ snapshot.status }}
                    </div>
                </div>

                <div class="rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
                    <h2 class="mb-4 font-heading text-lg font-semibold">Recommendations</h2>
                    <ul class="flex flex-col gap-3">
                        <li
                            v-for="(item, index) in snapshot.recommendations"
                            :key="index"
                            class="border-l-4 pl-3 text-sm text-airzen-muted"
                            :style="{ borderColor: snapshot.color }"
                        >
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <div
                    v-for="sensor in sensorStrip"
                    :key="sensor.label"
                    class="rounded-[22px] bg-airzen-card p-4 text-center shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]"
                >
                    <div class="text-xs uppercase tracking-wide text-airzen-muted">{{ sensor.label }}</div>
                    <div class="mt-1 font-heading text-xl font-semibold">{{ sensor.value }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
