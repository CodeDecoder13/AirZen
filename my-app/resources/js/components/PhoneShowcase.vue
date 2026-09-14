<script setup lang="ts">
import PhoneFrame from '@/components/PhoneFrame.vue';
import { Bell, ChevronDown, Leaf, ShieldCheck, Wind } from 'lucide-vue-next';
import { ref } from 'vue';

const track = ref<HTMLElement | null>(null);
const activeIndex = ref(0);

function onTrackScroll(): void {
    if (!track.value) return;
    const itemWidth = track.value.firstElementChild?.clientWidth ?? 1;
    activeIndex.value = Math.round(track.value.scrollLeft / (itemWidth + 16));
}

function scrollTo(index: number): void {
    if (!track.value) return;
    const item = track.value.children[index] as HTMLElement | undefined;
    item?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
}
</script>

<template>
    <div>
        <div
            ref="track"
            class="airzen-showcase-track flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 lg:grid lg:grid-cols-4 lg:justify-items-center lg:gap-6 lg:overflow-visible lg:pb-0"
            @scroll="onTrackScroll"
        >
            <!-- Home -->
            <div class="snap-center lg:pt-0">
                <PhoneFrame>
                    <div class="flex items-center justify-between pt-1 text-[11px] text-airzen-muted">
                        <span class="flex items-center gap-1 font-medium text-[#0B2A18]">Living room <ChevronDown :size="12" /></span>
                        <Bell :size="14" />
                    </div>
                    <p class="mt-2 font-heading text-[15px] font-semibold text-[#0B2A18]">Good morning</p>
                    <p class="text-[11px] text-airzen-muted">Your air is being monitored.</p>

                    <div
                        class="mt-3 flex flex-1 flex-col items-center justify-center gap-2 rounded-[18px] bg-airzen-card p-3 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_10px_20px_-14px_rgba(14,36,25,0.2)]"
                    >
                        <div class="flex w-full items-center justify-between text-[9px] uppercase text-airzen-muted">
                            <span>Overall AQI</span>
                            <span class="flex items-center gap-1 text-airzen-green-dark"><span class="h-1 w-1 rounded-full bg-airzen-green"></span>Live</span>
                        </div>
                        <div
                            class="relative flex h-24 w-24 items-center justify-center rounded-full"
                            style="background: conic-gradient(#22c55e 0deg 40.8deg, #eef3ef 40.8deg 360deg)"
                        >
                            <div class="absolute inset-2 rounded-full bg-airzen-card"></div>
                            <div class="relative flex flex-col items-center font-heading">
                                <span class="text-2xl font-bold text-airzen-good">34</span>
                                <span class="text-[8px] text-airzen-muted">AQI</span>
                            </div>
                        </div>
                        <span class="rounded-full bg-airzen-green-light px-2 py-0.5 text-[10px] font-medium text-airzen-green-dark">Good air</span>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <div class="rounded-[12px] bg-airzen-card p-2 text-[10px] font-medium text-[#0B2A18] shadow-[0_1px_2px_rgba(14,36,25,0.04)]">
                            Detailed AQI
                        </div>
                        <div class="rounded-[12px] bg-airzen-card p-2 text-[10px] font-medium text-[#0B2A18] shadow-[0_1px_2px_rgba(14,36,25,0.04)]">
                            Recommendations
                        </div>
                    </div>
                </PhoneFrame>
                <p class="mt-3 text-center text-xs font-medium text-airzen-muted">Home</p>
            </div>

            <!-- Breakdown -->
            <div class="snap-center lg:pt-6">
                <PhoneFrame time="9:42">
                    <div class="flex items-center justify-between pt-1 text-[11px] font-medium text-[#0B2A18]">
                        Living room
                        <span class="rounded-full bg-airzen-green-light px-2 py-0.5 text-[9px] text-airzen-green-dark">Sensors</span>
                    </div>

                    <div class="mt-3 rounded-[18px] bg-airzen-card p-3 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_10px_20px_-14px_rgba(14,36,25,0.2)]">
                        <p class="text-[9px] uppercase text-airzen-muted">Index score</p>
                        <div class="flex items-baseline gap-2">
                            <span class="font-heading text-2xl font-bold text-airzen-good">34</span>
                            <span class="text-[10px] text-airzen-muted">Good (0-50)</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#EEF3EF]">
                            <div class="h-full w-[11%] rounded-full bg-airzen-good"></div>
                        </div>
                    </div>

                    <div class="mt-2 rounded-[18px] bg-airzen-card p-3 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_10px_20px_-14px_rgba(14,36,25,0.2)]">
                        <p class="text-[9px] uppercase text-airzen-muted">PM2.5 &middot; real-time</p>
                        <svg viewBox="0 0 100 28" class="mt-1 h-7 w-full" preserveAspectRatio="none">
                            <path
                                d="M0 20 Q 12 22 20 16 T 40 14 T 60 20 T 80 8 T 100 12"
                                fill="none"
                                stroke="#22C55E"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <ul class="mt-2 flex-1 space-y-1.5">
                        <li class="flex items-center justify-between rounded-[12px] bg-airzen-card px-2.5 py-2 text-[10px] shadow-[0_1px_2px_rgba(14,36,25,0.04)]">
                            <span class="font-medium text-[#0B2A18]">PM2.5</span>
                            <span class="text-airzen-muted">8.4 &micro;g/m&sup3;</span>
                        </li>
                        <li class="flex items-center justify-between rounded-[12px] bg-airzen-card px-2.5 py-2 text-[10px] shadow-[0_1px_2px_rgba(14,36,25,0.04)]">
                            <span class="font-medium text-[#0B2A18]">Temperature</span>
                            <span class="text-airzen-muted">24.5&deg;C</span>
                        </li>
                    </ul>
                </PhoneFrame>
                <p class="mt-3 text-center text-xs font-medium text-airzen-muted">Breakdown</p>
            </div>

            <!-- Recommendations -->
            <div class="snap-center lg:pt-2">
                <PhoneFrame time="9:43">
                    <div class="flex items-center justify-between pt-1 text-[11px] font-medium text-[#0B2A18]">
                        Recommendations
                        <span class="rounded-full bg-airzen-green-light px-2 py-0.5 text-[9px] text-airzen-green-dark">34 &middot; Good</span>
                    </div>

                    <ul class="mt-3 flex-1 divide-y divide-[#EEF3EF] rounded-[18px] bg-airzen-card px-3 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_10px_20px_-14px_rgba(14,36,25,0.2)]">
                        <li class="flex items-start gap-2 py-2.5 first:pt-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-airzen-green-light text-airzen-green-dark">
                                <Leaf :size="12" />
                            </span>
                            <p class="pt-0.5 text-[10px] leading-snug text-[#28382F]">Keep windows and doors open to allow fresh air circulation.</p>
                        </li>
                        <li class="flex items-start gap-2 py-2.5">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-airzen-green-light text-airzen-green-dark">
                                <Wind :size="12" />
                            </span>
                            <p class="pt-0.5 text-[10px] leading-snug text-[#28382F]">Regularly clean and dust the space to minimize allergens.</p>
                        </li>
                        <li class="flex items-start gap-2 py-2.5 last:pb-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-airzen-green-light text-airzen-green-dark">
                                <ShieldCheck :size="12" />
                            </span>
                            <p class="pt-0.5 text-[10px] leading-snug text-[#28382F]">Indoor plants can help maintain this air quality.</p>
                        </li>
                    </ul>
                </PhoneFrame>
                <p class="mt-3 text-center text-xs font-medium text-airzen-muted">Recommendations</p>
            </div>

            <!-- History -->
            <div class="snap-center lg:pt-8">
                <PhoneFrame time="9:44">
                    <div class="flex items-center justify-between pt-1 text-[11px] font-medium text-[#0B2A18]">
                        Air history
                        <span class="text-[9px] text-airzen-muted">This week</span>
                    </div>

                    <div class="mt-3 flex-1 rounded-[18px] bg-airzen-card p-3 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_10px_20px_-14px_rgba(14,36,25,0.2)]">
                        <p class="text-[9px] uppercase text-airzen-muted">Weekly average</p>
                        <p class="font-heading text-xl font-bold text-[#0B2A18]">38 <span class="text-[10px] font-normal text-airzen-muted">avg</span></p>
                        <div class="mt-2 flex h-14 items-end gap-1.5">
                            <div
                                v-for="bar in [
                                    { h: 40, c: '#22C55E' },
                                    { h: 32, c: '#22C55E' },
                                    { h: 90, c: '#EAB308' },
                                    { h: 28, c: '#22C55E' },
                                    { h: 24, c: '#22C55E' },
                                    { h: 55, c: '#22C55E' },
                                    { h: 46, c: '#12A454' },
                                ]"
                                :key="bar.h"
                                class="flex-1 rounded-t-sm"
                                :style="{ height: `${bar.h}%`, backgroundColor: bar.c }"
                            ></div>
                        </div>
                    </div>
                </PhoneFrame>
                <p class="mt-3 text-center text-xs font-medium text-airzen-muted">History</p>
            </div>
        </div>

        <div class="mt-1 flex justify-center gap-1.5 lg:hidden">
            <button
                v-for="index in 4"
                :key="index"
                type="button"
                class="h-1.5 rounded-full transition-all"
                :class="activeIndex === index - 1 ? 'w-4 bg-airzen-green' : 'w-1.5 bg-[#D7E8DD]'"
                :aria-label="`Show phone ${index}`"
                @click="scrollTo(index - 1)"
            ></button>
        </div>
    </div>
</template>

<style scoped>
.airzen-showcase-track {
    scrollbar-width: none;
}
.airzen-showcase-track::-webkit-scrollbar {
    display: none;
}
</style>
