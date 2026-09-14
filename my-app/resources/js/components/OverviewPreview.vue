<script setup lang="ts">
import { Atom, Droplets, Flame, Leaf, ShieldCheck, Thermometer, Wind, type LucideIcon } from 'lucide-vue-next';

interface Metric {
    key: string;
    label: string;
    icon: LucideIcon;
    value: string;
    unit: string;
    wide?: boolean;
}

const metrics: Metric[] = [
    { key: 'pm25', label: 'PM2.5', icon: Wind, value: '8', unit: 'µg/m³' },
    { key: 'temperature', label: 'Temperature', icon: Thermometer, value: '24.5', unit: '°C' },
    { key: 'humidity', label: 'Humidity', icon: Droplets, value: '55', unit: '%' },
    { key: 'co', label: 'CO', icon: Flame, value: '4.5', unit: 'signal' },
    { key: 'nitrogen', label: 'Nitrogen', icon: Atom, value: '40.0', unit: 'signal', wide: true },
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
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10"></div>
                <div class="absolute -right-2 top-8 h-24 w-24 rounded-full border border-white/10"></div>

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
                    :class="metric.wide ? 'col-span-2 flex items-center justify-between' : 'flex flex-col justify-between'"
                >
                    <template v-if="metric.wide">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-[#2A8362]">
                                <component :is="metric.icon" :size="14" />
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">{{ metric.label }}</span>
                        </div>
                        <p class="az2-display text-2xl text-[#1D352D]">
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
                        <p class="az2-display mt-3 text-2xl text-[#1D352D]">
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
</style>
