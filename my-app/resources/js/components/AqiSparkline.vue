<script setup lang="ts">
import type { TrendPoint } from '@/composables/useReadingHistory';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        points: TrendPoint[];
        color?: string;
    }>(),
    { color: '#12A454' },
);

const VIEW_WIDTH = 320;
const VIEW_HEIGHT = 96;
const PADDING_Y = 10;

const coords = computed(() => {
    if (props.points.length < 2) return [];

    const values = props.points.map((p) => p.value);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const step = VIEW_WIDTH / (props.points.length - 1);

    return props.points.map((point, index) => {
        const normalized = (point.value - min) / range;
        const y = VIEW_HEIGHT - PADDING_Y - normalized * (VIEW_HEIGHT - PADDING_Y * 2);
        return { x: index * step, y };
    });
});

// Quadratic-midpoint smoothing: turns a straight polyline into a gentle
// curve using only the real sampled points - no data is invented.
const linePath = computed(() => {
    if (coords.value.length < 2) return '';

    let path = `M ${coords.value[0].x} ${coords.value[0].y}`;

    // Curve through every point except the last via its midpoint (keeps the
    // line smooth without ever passing outside the real sampled values),
    // then a plain line into the final point - a trailing reflected control
    // point (SVG's "T" shorthand) can overshoot past the last sample.
    for (let i = 0; i < coords.value.length - 2; i++) {
        const current = coords.value[i];
        const next = coords.value[i + 1];
        const midX = (current.x + next.x) / 2;
        const midY = (current.y + next.y) / 2;
        path += ` Q ${current.x} ${current.y} ${midX} ${midY}`;
    }

    const last = coords.value[coords.value.length - 1];
    path += ` L ${last.x} ${last.y}`;

    return path;
});

const areaPath = computed(() => (linePath.value ? `${linePath.value} L ${VIEW_WIDTH} ${VIEW_HEIGHT} L 0 ${VIEW_HEIGHT} Z` : ''));

const latestPoint = computed(() => coords.value.at(-1));
</script>

<template>
    <div class="relative">
        <svg v-if="coords.length >= 2" :viewBox="`0 0 ${VIEW_WIDTH} ${VIEW_HEIGHT}`" preserveAspectRatio="none" class="h-24 w-full overflow-visible">
            <defs>
                <linearGradient :id="`sparkline-fill-${color}`" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="color" stop-opacity="0.18" />
                    <stop offset="100%" :stop-color="color" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path :d="areaPath" :fill="`url(#sparkline-fill-${color})`" stroke="none" />
            <path :d="linePath" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <circle v-if="latestPoint" class="airzen-sparkline-dot" :cx="latestPoint.x" :cy="latestPoint.y" r="3.5" :fill="color" />
            <circle v-if="latestPoint" class="airzen-sparkline-ping" :cx="latestPoint.x" :cy="latestPoint.y" r="3.5" fill="none" :stroke="color" stroke-width="1.5" />
        </svg>
        <div v-else class="flex h-24 items-center justify-center text-sm text-airzen-muted">Not enough readings yet to chart a trend.</div>
    </div>
</template>

<style scoped>
.airzen-sparkline-ping {
    transform-origin: center;
    animation: airzen-sparkline-ping 2.4s ease-out infinite;
}

@keyframes airzen-sparkline-ping {
    0% {
        r: 3.5;
        opacity: 0.8;
    }
    100% {
        r: 10;
        opacity: 0;
    }
}
</style>
