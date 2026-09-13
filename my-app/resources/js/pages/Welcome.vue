<script setup lang="ts">
import HeroScene from '@/components/HeroScene.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="AirZen — Indoor Air Quality Monitoring">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=space-grotesk:500,600,700|public-sans:400,500,600" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-airzen-bg font-sans text-[#1b1b18]">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between p-6 text-sm">
            <div class="font-heading text-lg font-semibold text-airzen-green-dark">AirZen</div>
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-full bg-airzen-green px-5 py-1.5 text-sm leading-normal text-white hover:bg-airzen-green-dark"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="inline-block rounded-full px-5 py-1.5 text-sm leading-normal text-airzen-muted hover:text-airzen-green-dark">
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-full bg-airzen-green px-5 py-1.5 text-sm leading-normal text-white hover:bg-airzen-green-dark"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto grid w-full max-w-5xl gap-10 px-6 pb-20 pt-6 md:grid-cols-2 md:items-center">
            <div>
                <h1 class="font-heading text-4xl font-bold leading-tight text-[#0B2A18] md:text-5xl">Know your air, in real time.</h1>
                <p class="mt-4 text-base text-airzen-muted">
                    AirZen turns a low-cost ESP32 sensor node into a live indoor air quality monitor &mdash; tracking temperature, humidity, CO, NOx,
                    and PM2.5, then translating every reading into an EPA-standard AQI score and a plain-language recommendation for the room.
                </p>
                <div class="mt-8 flex gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-block rounded-full bg-airzen-green px-6 py-2.5 text-sm font-medium text-white hover:bg-airzen-green-dark"
                    >
                        Open dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-block rounded-full bg-airzen-green px-6 py-2.5 text-sm font-medium text-white hover:bg-airzen-green-dark"
                        >
                            Log in to AirZen
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-block rounded-full bg-airzen-green-light px-6 py-2.5 text-sm font-medium text-airzen-green-dark hover:bg-airzen-green/20"
                        >
                            Create an account
                        </Link>
                    </template>
                </div>
            </div>

            <HeroScene />
        </main>
    </div>
</template>
