<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        points: number[];
        color?: string;
    }>(),
    { color: '#2A8362' },
);

const VIEW_WIDTH = 100;
const VIEW_HEIGHT = 28;
const PADDING_Y = 3;

const linePath = computed(() => {
    if (props.points.length < 2) return '';

    const min = Math.min(...props.points);
    const max = Math.max(...props.points);
    const range = max - min || 1;
    const step = VIEW_WIDTH / (props.points.length - 1);

    const coords = props.points.map((value, index) => {
        const normalized = (value - min) / range;
        const y = VIEW_HEIGHT - PADDING_Y - normalized * (VIEW_HEIGHT - PADDING_Y * 2);
        return { x: index * step, y };
    });

    let path = `M ${coords[0].x} ${coords[0].y}`;
    for (let i = 0; i < coords.length - 2; i++) {
        const current = coords[i];
        const next = coords[i + 1];
        const midX = (current.x + next.x) / 2;
        const midY = (current.y + next.y) / 2;
        path += ` Q ${current.x} ${current.y} ${midX} ${midY}`;
    }
    const last = coords[coords.length - 1];
    path += ` L ${last.x} ${last.y}`;

    return path;
});
</script>

<template>
    <svg :viewBox="`0 0 ${VIEW_WIDTH} ${VIEW_HEIGHT}`" preserveAspectRatio="none" class="h-7 w-full overflow-visible">
        <path
            class="airzen-mini-sparkline-path"
            :d="linePath"
            fill="none"
            :stroke="color"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            pathLength="1"
        />
    </svg>
</template>

<style scoped>
.airzen-mini-sparkline-path {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
    animation: airzen-mini-sparkline-draw 900ms cubic-bezier(0.23, 1, 0.32, 1) forwards;
}

@keyframes airzen-mini-sparkline-draw {
    to {
        stroke-dashoffset: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .airzen-mini-sparkline-path {
        animation: none;
        stroke-dashoffset: 0;
    }
}
</style>
