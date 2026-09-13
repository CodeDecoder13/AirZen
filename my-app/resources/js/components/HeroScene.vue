<script setup lang="ts">
withDefaults(defineProps<{ color?: string }>(), { color: '#22C55E' });
</script>

<template>
    <div class="rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
        <svg viewBox="0 0 400 300" class="w-full" :style="{ '--device-color': color }">
            <!-- floor -->
            <polygon points="60,220 200,290 340,220 200,150" fill="#E3F6EA" stroke="#B7DFC5" stroke-width="2" />
            <!-- left wall (shadow) -->
            <polygon points="60,220 200,150 200,40 60,110" fill="#C7E7D4" stroke="#B7DFC5" stroke-width="2" />
            <!-- right wall (light) -->
            <polygon points="340,220 200,150 200,40 340,110" fill="#D8F0E1" stroke="#B7DFC5" stroke-width="2" />

            <!-- window on left wall -->
            <rect x="95" y="80" width="50" height="50" rx="4" fill="#FFF6D9" stroke="#E8D48A" stroke-width="2" />

            <!-- bookshelf on left wall -->
            <rect x="80" y="150" width="45" height="55" fill="#BFE3CE" stroke="#9FCBAF" stroke-width="1.5" />
            <line x1="80" y1="168" x2="125" y2="168" stroke="#9FCBAF" stroke-width="1.5" />
            <line x1="80" y1="186" x2="125" y2="186" stroke="#9FCBAF" stroke-width="1.5" />

            <!-- potted plant on floor -->
            <ellipse cx="270" cy="240" rx="14" ry="7" fill="#0B7A3F" opacity="0.5" />
            <rect x="262" y="225" width="16" height="15" rx="2" fill="#8A5A3B" />
            <circle cx="270" cy="215" r="12" fill="#2FAE6A" />

            <!-- sensor device on right wall -->
            <g transform="translate(255,90)">
                <rect x="0" y="0" width="40" height="26" rx="6" fill="#FFFFFF" stroke="var(--device-color)" stroke-width="2" />
                <circle class="airzen-led" cx="20" cy="13" r="4" fill="var(--device-color)" />

                <!-- ping rings -->
                <circle class="airzen-ping airzen-ping-1" cx="20" cy="13" r="6" fill="none" stroke="var(--device-color)" stroke-width="2" />
                <circle class="airzen-ping airzen-ping-2" cx="20" cy="13" r="6" fill="none" stroke="var(--device-color)" stroke-width="2" />

                <!-- wifi arcs -->
                <path class="airzen-wifi airzen-wifi-1" d="M 10 -6 A 14 14 0 0 1 30 -6" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" />
                <path class="airzen-wifi airzen-wifi-2" d="M 4 -14 A 22 22 0 0 1 36 -14" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" opacity="0.7" />
                <path class="airzen-wifi airzen-wifi-3" d="M -2 -22 A 30 30 0 0 1 42 -22" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" opacity="0.5" />

                <!-- drifting particles -->
                <circle class="airzen-particle airzen-particle-1" cx="8" cy="30" r="2" fill="var(--device-color)" />
                <circle class="airzen-particle airzen-particle-2" cx="20" cy="34" r="2" fill="var(--device-color)" />
                <circle class="airzen-particle airzen-particle-3" cx="32" cy="30" r="2" fill="var(--device-color)" />
            </g>
        </svg>
        <p class="mt-3 text-center text-sm text-airzen-muted">Sensor mounted in Library Room 204 &middot; actively sampling</p>
    </div>
</template>

<style scoped>
.airzen-led {
    animation: airzen-pulse 1.8s ease-in-out infinite;
    transform-origin: center;
}

.airzen-ping {
    transform-origin: center;
    animation: airzen-ping-expand 2.4s ease-out infinite;
}
.airzen-ping-2 {
    animation-delay: 1.2s;
}

.airzen-wifi {
    animation: airzen-wifi-fade 2s ease-in-out infinite;
}
.airzen-wifi-2 {
    animation-delay: 0.25s;
}
.airzen-wifi-3 {
    animation-delay: 0.5s;
}

.airzen-particle {
    animation: airzen-drift 3s ease-in infinite;
}
.airzen-particle-2 {
    animation-delay: 1s;
}
.airzen-particle-3 {
    animation-delay: 2s;
}

@keyframes airzen-pulse {
    0%, 100% { opacity: 0.6; transform: scale(0.9); }
    50% { opacity: 1; transform: scale(1.15); }
}

@keyframes airzen-ping-expand {
    0% { r: 6; opacity: 0.8; }
    100% { r: 22; opacity: 0; }
}

@keyframes airzen-wifi-fade {
    0%, 100% { opacity: 0.15; }
    50% { opacity: 0.9; }
}

@keyframes airzen-drift {
    0% { transform: translateY(0); opacity: 0.8; }
    100% { transform: translateY(-24px); opacity: 0; }
}
</style>
