<script setup lang="ts">
import OverviewPreview from '@/components/OverviewPreview.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Leaf } from 'lucide-vue-next';
</script>

<template>
    <Head title="AirZen — Indoor Air Quality Monitoring">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600|dm-sans:400,500,700" rel="stylesheet" />
    </Head>

    <div class="az2-sans min-h-screen bg-[#F7F8F1] text-[#1D352D]">
        <header class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 text-sm sm:px-6 sm:py-6">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1A3C30] text-[#C6DF58]">
                    <Leaf :size="16" />
                </span>
                <div class="leading-none">
                    <span class="az2-display block text-lg text-[#1D352D]">AirZen</span>
                    <span class="block text-[9px] font-bold uppercase tracking-[0.14em] text-[#6B8577]">The air, understood</span>
                </div>
            </div>
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-full bg-[#2A8362] px-5 py-2 text-sm font-bold text-white hover:bg-[#226b4f]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="inline-block rounded-full px-4 py-2 text-sm font-medium text-[#6B8577] hover:text-[#1D352D]">
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-full bg-[#2A8362] px-5 py-2 text-sm font-bold text-white hover:bg-[#226b4f]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-6 sm:px-6 sm:pb-20 sm:pt-10">
            <OverviewPreview />
        </main>
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
