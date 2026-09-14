<script setup lang="ts">
withDefaults(defineProps<{ time?: string }>(), { time: '9:41' });
</script>

<template>
    <div class="relative w-[240px] shrink-0 rounded-[38px] bg-[#111814] p-2 shadow-[0_30px_60px_-24px_rgba(14,36,25,0.45)]">
        <div class="relative aspect-[9/19.5] w-full overflow-hidden rounded-[30px] bg-airzen-bg">
            <div class="flex items-center justify-between px-5 pb-1 pt-3 font-heading text-[11px] font-semibold text-[#0B2A18]">
                <span>{{ time }}</span>
                <span class="flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#0B2A18]"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-[#0B2A18]"></span>
                    <span class="h-1.5 w-2.5 rounded-sm bg-[#0B2A18]"></span>
                </span>
            </div>
            <div class="absolute left-1/2 top-2 h-5 w-24 -translate-x-1/2 rounded-full bg-[#111814]"></div>
            <div class="flex h-[calc(100%-28px)] flex-col overflow-hidden px-3 pb-3">
                <slot />
            </div>
            <div class="absolute bottom-1.5 left-1/2 h-1 w-24 -translate-x-1/2 rounded-full bg-[#0B2A18]/20"></div>
        </div>
    </div>
</template>
