<script setup lang="ts">
import MiniSparkline from '@/components/MiniSparkline.vue';
import { Atom, Droplets, Flame, Leaf, ShieldCheck, Thermometer, Wind, type LucideIcon } from 'lucide-vue-next';

interface Metric {
    key: string;
    label: string;
    icon: LucideIcon;
    value: string;
    unit: string;
    series: number[];
    wide?: boolean;
}

const metrics: Metric[] = [
    { key: 'pm25', label: 'PM2.5', icon: Wind, value: '8', unit: 'µg/m³', series: [6, 9, 7, 11, 8, 6, 8] },
    { key: 'temperature', label: 'Temperature', icon: Thermometer, value: '24.5', unit: '°C', series: [23.8, 24.1, 23.9, 24.6, 24.3, 24.8, 24.5] },
    { key: 'humidity', label: 'Humidity', icon: Droplets, value: '55', unit: '%', series: [58, 54, 56, 52, 55, 57, 55] },
    { key: 'co', label: 'CO', icon: Flame, value: '4.5', unit: 'signal', series: [3.8, 4.4, 4.0, 4.9, 4.2, 4.7, 4.5] },
    { key: 'nitrogen', label: 'Nitrogen', icon: Atom, value: '40.0', unit: 'signal', series: [36, 42, 38, 45, 39, 41, 40], wide: true },
];

const recommendations = [
    { icon: Leaf, text: 'Keep windows and doors open to allow fresh air circulation.' },
    { icon: Wind, text: 'Regularly clean and dust the space to minimize allergens.' },
    { icon: ShieldCheck, text: 'Indoor plants like peace lilies can help maintain this air quality.' },
];
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

                <p class="relative flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.1em] text-white/70">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
                    Air quality now
                </p>
                <h3 class="az2-display relative mt-2 text-3xl leading-[1.05] sm:text-4xl">
                    Your home feels<br />
                    <span class="text-[#C6DF58]">clear.</span>
                </h3>
                <p class="relative mt-3 max-w-[32ch] text-sm text-white/70">A calm reading across the room. Nothing needs your attention right now.</p>

                <div class="relative mt-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="flex items-baseline gap-2">
                        <span class="az2-display text-6xl text-[#C6DF58]">34</span>
                        <span class="pb-1">
                            <span class="block text-base font-bold">Good</span>
                            <span class="block text-xs text-white/60">out of 300 &middot; PM2.5-driven</span>
                        </span>
                    </div>
                    <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                        <ShieldCheck :size="14" />
                        No action needed
                    </span>
                </div>
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
                        <MiniSparkline :points="metric.series" color="#2A8362" class="w-20" />
                        <p class="az2-display shrink-0 text-2xl text-[#1D352D]">
                            {{ metric.value }}<span class="ml-0.5 text-xs font-normal text-[#6B8577]">{{ metric.unit }}</span>
                        </p>
                    </template>
                    <template v-else>
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-[#2A8362]">
                                <component :is="metric.icon" :size="14" />
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">{{ metric.label }}</span>
                        </div>
                        <MiniSparkline :points="metric.series" color="#2A8362" />
                        <p class="az2-display text-2xl text-[#1D352D]">
                            {{ metric.value }}<span class="ml-0.5 text-xs font-normal text-[#6B8577]">{{ metric.unit }}</span>
                        </p>
                    </template>
                </div>
            </div>
        </div>

        <div class="border-t border-[#EEF1E9] p-5 md:p-6">
            <h4 class="az2-display text-xl text-[#1D352D]">A little care, at the right time.</h4>
            <p class="mt-1 text-sm text-[#6B8577]">Suggestions, never alarms.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                <li v-for="(item, index) in recommendations" :key="index" class="flex items-start gap-2.5 rounded-[16px] bg-[#F7F8F1] p-3.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2A8362]/10 text-[#2A8362]">
                        <component :is="item.icon" :size="14" />
                    </span>
                    <p class="text-xs leading-relaxed text-[#3C4A41]">{{ item.text }}</p>
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
