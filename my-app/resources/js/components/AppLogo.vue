<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#1A3C30] text-[#C6DF58]">
        <AppLogoIcon class="size-4" />
    </div>
    <div class="ml-1 grid flex-1 text-left leading-none">
        <span class="truncate text-sm font-semibold">AirZen</span>
        <span class="truncate text-[9px] font-bold uppercase tracking-[0.12em] text-sidebar-foreground/60">Your Indoor Companion</span>
    </div>
</template>
