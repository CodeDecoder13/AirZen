# AirZen v2 Brand Rollout + Live Motion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add ambient "live air" motion to the Welcome.vue hero card, add live up/down mini-charts to its 5 sensor cards, and roll the same AirZen v2 brand (cream `#F7F8F1`, dark green `#1A3C30`, primary `#2A8362`, lime `#C6DF58`, Fraunces + DM Sans) out to the real Dashboard.vue so both pages read as one product.

**Architecture:** Two presentational Vue components (`MiniSparkline.vue`, ambient motion baked into `OverviewPreview.vue`'s existing hero markup) cover the Welcome.vue enhancements. Dashboard.vue is restyled in place: its real data composables (`useLatestReading`, `useReadingHistory`) and chart components (`AqiSparkline`, `WeeklyAqiBars`) are untouched (they're data-correct, already tested), only their color/typography/container classes change, and the circular `AqiGauge` ring is replaced with the same big-card-plus-lime-number hero treatment already proven in `OverviewPreview.vue`, for one consistent visual language across both pages.

**Tech Stack:** Vue 3 `<script setup>`, Tailwind arbitrary values (no new dependency), native CSS animations (`@keyframes`/`transition`), `lucide-vue-next` icons (already a dependency). No backend changes — this plan is 100% frontend.

**Spec:** This plan document is self-contained; it implements the user's request directly (no separate spec file). Prior art to match: `resources/js/components/OverviewPreview.vue` and `resources/js/pages/Welcome.vue` (the already-shipped AirZen v2 brand), and `resources/js/pages/Dashboard.vue` (the page being rebranded, whose real-data logic must not change).

## Global Constraints

- Brand tokens (exact hex, do not invent new ones): background `#F7F8F1`, dark green `#1A3C30`, primary green `#2A8362`, primary hover `#226b4f`, lime accent `#C6DF58`, body text `#1D352D`, muted text `#6B8577`, secondary text on dark `#3C4A41`/white-70%.
- Fonts: display headings use Fraunces (`.az2-display` scoped class, already defined in `Welcome.vue` and `OverviewPreview.vue` — reuse the same class name and font stack, don't redefine it differently), body uses DM Sans (`.az2-sans`).
- Card radius: `rounded-[28px]` for outer containers, `rounded-[18px]`/`rounded-[16px]` for inner cards — matches `OverviewPreview.vue`'s existing scale. Do not introduce a third radius value.
- Only animate `transform` and `opacity` (plus `stroke-dasharray`/`stroke-dashoffset` for SVG line draw-ins, which are GPU-cheap). Never animate `width`, `height`, `top`, `left` — this was flagged and fixed twice already in this codebase by the impeccable detector.
- Every new animation must respect `prefers-reduced-motion: reduce` (drop to a static, still-legible end state).
- No fabricated live claims: the Welcome.vue page is pre-auth marketing content and its sample data is already established as illustrative (matches the reference share-card pattern); Dashboard.vue must keep using its real polled data untouched.
- Since this is a pure frontend/CSS/Vue-template task, "tests" in this plan are manual browser verification steps plus a run of the impeccable mechanical detector (`node C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs --json <files>`) — there is no meaningful PHPUnit/Vitest coverage for visual polish in this codebase (no frontend test runner is installed, a constraint already established earlier in this project).
- Run `php artisan test` after every task anyway, to catch any accidental prop/contract break in Dashboard.vue (it consumes real `initialSnapshot`/`initialHistory` props whose shape must not change).

---

### Task 1: `MiniSparkline.vue` + live up/down charts on Welcome.vue's 5 metric cards

**Files:**
- Create: `resources/js/components/MiniSparkline.vue`
- Modify: `resources/js/components/OverviewPreview.vue`

**Interfaces:**
- Produces: `MiniSparkline.vue` — props `{ points: number[]; color?: string }` (defaults `color` to `#2A8362`), renders a small SVG line (no library, matches the hand-rolled `AqiSparkline.vue` pattern already in this codebase). Consumed by Task 1 only (Welcome.vue's illustrative preview); Task 3 does not need it since Dashboard.vue's real sparkline (`AqiSparkline.vue`) already exists and stays as-is.

- [ ] **Step 1: Write `MiniSparkline.vue`**

```vue
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
```

Note: `pathLength="1"` normalizes the path's total length to `1` regardless of its actual geometry, so `stroke-dasharray: 1` / `stroke-dashoffset: 1→0` always draws the full line in one clean sweep no matter how wiggly the data is — no per-path length calculation needed in JS.

- [ ] **Step 2: Add illustrative sample series + wire into each metric card**

In `OverviewPreview.vue`, extend the `Metric` interface and each entry with a `series: number[]` (5-9 points, clearly trending up-and-down, not monotonic — these are the same kind of illustrative sample values already used for `value`/`unit` on this pre-auth marketing page):

```ts
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
```

Import and place `<MiniSparkline>` inside each card, between the icon/label row and the value line:

```vue
<script setup lang="ts">
import MiniSparkline from '@/components/MiniSparkline.vue';
// ...existing imports...
</script>
```

For the non-wide cards' template (inside the `v-else` branch of the existing `v-for`):

```html
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
```

For the wide (Nitrogen) card, add a `<MiniSparkline>` sized narrower between the label block and the value (e.g. wrap it `class="w-24"` so it sits between the left label group and the right value in the existing flex row).

- [ ] **Step 3: Build and verify in browser**

Run: `npm run build`
Then start the dev server and open `/` (no login needed): confirm all 5 cards show a small green line that draws in on page load, each with a visibly different up-and-down shape (not a flat line), and the existing value/unit text is unchanged.

- [ ] **Step 4: Run the impeccable detector**

Run: `node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json resources/js/components/MiniSparkline.vue resources/js/components/OverviewPreview.vue`
Expected: `[]` (no findings). If it flags anything, fix it before moving on.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/MiniSparkline.vue resources/js/components/OverviewPreview.vue
git commit -m "feat: add live up/down mini-charts to Welcome.vue's 5 sensor cards"
```

---

### Task 2: Ambient "live air" animation on the hero card

**Files:**
- Modify: `resources/js/components/OverviewPreview.vue`

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing consumed elsewhere — purely decorative, scoped to this component's `<style>` block.

- [ ] **Step 1: Animate the two existing decorative ring outlines**

The hero card already has two plain `border border-white/10` circles (lines with `class="absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10"` and the smaller one below it). Give them a class and a slow breathing pulse:

```html
<div class="airzen-air-ring airzen-air-ring-1 absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10"></div>
<div class="airzen-air-ring airzen-air-ring-2 absolute -right-2 top-8 h-24 w-24 rounded-full border border-white/10"></div>
```

- [ ] **Step 2: Add 4 drifting "air particle" dots**

Add this markup right after the two ring divs (still inside the green hero card, before the eyebrow `<p>`):

```html
<span class="airzen-air-particle airzen-air-particle-1 absolute right-16 top-20 h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
<span class="airzen-air-particle airzen-air-particle-2 absolute right-28 top-32 h-1 w-1 rounded-full bg-[#C6DF58]"></span>
<span class="airzen-air-particle airzen-air-particle-3 absolute right-10 top-40 h-1.5 w-1.5 rounded-full bg-white/60"></span>
<span class="airzen-air-particle airzen-air-particle-4 absolute right-36 top-16 h-1 w-1 rounded-full bg-white/60"></span>
```

- [ ] **Step 3: Add the keyframes and stagger**

In `OverviewPreview.vue`'s `<style scoped>` block, add:

```css
.airzen-air-ring {
    animation: airzen-air-ring-pulse 4s ease-in-out infinite;
    transform-origin: center;
}
.airzen-air-ring-2 {
    animation-delay: 1.3s;
}

@keyframes airzen-air-ring-pulse {
    0%, 100% {
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
```

This is the page's one authored motion moment for this card: the rings read as a slow "breathing" pulse (state indication — air is being actively sensed) and the particles read as motes drifting upward through the room, matching "animating a real air" without becoming a busy/looping distraction (6s cycle, staggered, low-opacity).

- [ ] **Step 4: Verify in browser**

Reload `/`, watch the green hero card for ~6 seconds: confirm the two ring outlines pulse gently, and confirm 4 small dots fade in and drift upward on a staggered loop without ever all appearing/disappearing in sync (the staggered `animation-delay` values prevent that). Confirm nothing overlaps the "34 Good" score or the "No action needed" pill (the particles' `right-*`/`top-*` positions keep them in the card's upper-right quadrant, away from that content).

- [ ] **Step 5: Run the impeccable detector**

Run: `node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json resources/js/components/OverviewPreview.vue`
Expected: `[]`.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/OverviewPreview.vue
git commit -m "feat: add ambient live-air animation to the hero card"
```

---

### Task 3: Rebrand Dashboard.vue to match Welcome.vue's AirZen v2 look

**Files:**
- Modify: `resources/js/pages/Dashboard.vue`
- Modify: `resources/js/components/AqiGauge.vue` — actually **removed from use** on the dashboard (see below); the component file itself is left alone (unused, not deleted, consistent with this codebase's existing practice of not deleting components that might be reused later).
- Modify: `resources/js/components/AqiSparkline.vue` (only the hardcoded band colors used for its gradient fallback default, if any — check before changing; the component takes `color` as a prop already, so most of the time no change is needed here).
- Modify: `resources/js/components/WeeklyAqiBars.vue` (only if its internal `BANDS` hex list needs to stay — it should NOT change, those are real EPA AQI band colors used for correctness, not brand decoration; leave this file untouched).

**Interfaces:**
- Consumes: `useLatestReading`, `useReadingHistory`, `AqiSparkline`, `WeeklyAqiBars` — all unchanged, same props/types as today. `initialSnapshot`/`initialHistory` prop shapes are untouched (backend contract, do not modify).
- Produces: nothing new consumed elsewhere.

- [ ] **Step 1: Confirm the backend contract test still exists and passes before touching anything**

Run: `php artisan test --filter=DashboardPropsTest`
Expected: PASS (2 tests) — this proves `initialSnapshot`/`initialHistory` props are correct before the reskin, so any regression after Step 2 is visibly a frontend mistake, not a prop-shape break.

- [ ] **Step 2: Rewrite `Dashboard.vue`'s template and add the font import**

Replace the full `<template>` block with:

```vue
<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Head>
            <link rel="preconnect" href="https://fonts.bunny.net" />
            <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600|dm-sans:400,500,700" rel="stylesheet" />
        </Head>

        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div class="airzen-section flex flex-wrap items-center justify-between gap-3" style="animation-delay: 0ms">
                <div>
                    <h1 class="az2-display text-2xl text-[#1D352D]">{{ greeting }}, {{ page.props.auth.user.name }}</h1>
                    <p class="text-sm text-[#6B8577]">Your indoor air is being monitored.</p>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-[#6B8577] shadow-[0_1px_2px_rgba(20,35,25,0.04),0_16px_32px_-20px_rgba(20,35,25,0.2)]">
                    <span class="airzen-live-dot h-2 w-2 rounded-full bg-[#2A8362]"></span>
                    {{ formatUpdatedAt(snapshot.updated_at) }}
                </div>
            </div>

            <div class="airzen-section grid gap-6 lg:grid-cols-5" style="animation-delay: 60ms">
                <div class="relative overflow-hidden rounded-[22px] bg-[#2A8362] p-8 text-white lg:col-span-2">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10"></div>
                    <div class="absolute -right-2 top-8 h-24 w-24 rounded-full border border-white/10"></div>

                    <template v-if="hasAnyReading">
                        <p class="relative flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.1em] text-white/70">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#C6DF58]"></span>
                            Air quality now
                        </p>
                        <h3 class="az2-display relative mt-2 text-3xl leading-[1.05]">
                            {{ statusHeadline.line1 }}<br />
                            <span class="text-[#C6DF58]">{{ statusHeadline.line2 }}</span>
                        </h3>
                        <p class="relative mt-3 max-w-[30ch] text-sm text-white/70">{{ statusDescription }}</p>

                        <div class="relative mt-6 flex flex-wrap items-end justify-between gap-4">
                            <div class="flex items-baseline gap-2">
                                <span class="az2-display text-6xl text-[#C6DF58]">{{ Math.round(snapshot.aqi) }}</span>
                                <span class="pb-1">
                                    <span class="block text-base font-bold">{{ snapshot.status }}</span>
                                    <span class="block text-xs text-white/60">out of 300 &middot; PM2.5-driven</span>
                                </span>
                            </div>
                            <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                                {{ formatUpdatedAt(snapshot.updated_at) }}
                            </span>
                        </div>
                    </template>
                    <p v-else class="relative py-16 text-center text-sm text-white/80">Waiting for the first sensor reading&hellip;</p>
                </div>

                <div class="rounded-[22px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)] lg:col-span-3">
                    <h2 class="az2-display text-xl text-[#1D352D]">A little care, at the right time.</h2>
                    <p class="mt-1 text-sm text-[#6B8577]">Suggestions, never alarms.</p>
                    <ul class="mt-4 divide-y divide-[#EEF1E9]">
                        <li v-for="(item, index) in snapshot.recommendations" :key="index" class="flex items-start gap-3 py-3 first:pt-3 last:pb-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A8362]/10 text-[#2A8362]">
                                <component :is="RECOMMENDATION_ICONS[index % RECOMMENDATION_ICONS.length]" :size="18" :stroke-width="2" />
                            </span>
                            <p class="pt-1.5 text-sm leading-relaxed text-[#3C4A41]">{{ item }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="airzen-section rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]" style="animation-delay: 120ms">
                <h2 class="az2-display text-xl text-[#1D352D]">Breakdown</h2>

                <div class="mt-5">
                    <div class="flex items-baseline justify-between text-xs text-[#6B8577]">
                        <span>Index score</span>
                        <span class="az2-display text-2xl" :style="{ color: snapshot.color }">{{ Math.round(snapshot.aqi) }}</span>
                    </div>
                    <div class="relative mt-2 h-2.5 overflow-hidden rounded-full bg-[#EEF1E9]">
                        <div
                            class="airzen-index-fill absolute inset-y-0 left-0 w-full origin-left rounded-full"
                            :style="{ transform: `scaleX(${indexFillScale})`, backgroundColor: snapshot.color }"
                        ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-[11px] text-[#6B8577]">
                        <span v-for="tick in scaleTicks" :key="tick">{{ tick }}</span>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-[#6B8577]">PM2.5 &middot; recent trend</p>
                    <AqiSparkline :points="history.trend" :color="snapshot.color" />
                </div>

                <ul class="mt-2 divide-y divide-[#EEF1E9]">
                    <li v-for="metric in metrics" :key="metric.key" class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F7F8F1] text-[#2A8362]">
                            <component :is="metric.icon" :size="17" :stroke-width="2" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-[#1D352D]">{{ metric.label }}</span>
                                <span
                                    v-if="metric.badge"
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :style="{ backgroundColor: metric.badge.color + '22', color: metric.badge.color }"
                                >
                                    {{ metric.badge.text }}
                                </span>
                            </div>
                            <p class="truncate text-xs text-[#6B8577]">{{ metric.description }}</p>
                        </div>
                        <span class="az2-display shrink-0 text-base tabular-nums text-[#1D352D]">
                            {{ metric.value === null ? '\u2013' : `${metric.value.toFixed(metric.decimals)}${metric.unit}` }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="airzen-section rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]" style="animation-delay: 180ms">
                <h2 class="az2-display text-xl text-[#1D352D]">This week</h2>
                <p class="text-xs text-[#6B8577]">Daily average AQI, from PM2.5 readings.</p>
                <div class="mt-5">
                    <WeeklyAqiBars :days="history.weekly" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 3: Update the `<script setup>` block**

Remove the `AqiGauge` import (no longer used) and add a `statusHeadline` computed that mirrors `OverviewPreview.vue`'s "Your home feels clear." two-line pattern, driven by the real `snapshot.status` instead of a hardcoded string:

```ts
import AqiSparkline from '@/components/AqiSparkline.vue';
import WeeklyAqiBars from '@/components/WeeklyAqiBars.vue';
// AqiGauge import removed - replaced by the big-card treatment below
```

Add near `STATUS_DESCRIPTIONS`:

```ts
const STATUS_HEADLINES: Record<string, { line1: string; line2: string }> = {
    Good: { line1: 'Your home feels', line2: 'clear.' },
    Normal: { line1: 'Your home feels', line2: 'steady.' },
    'Unhealthy for Sensitive Groups': { line1: 'Your home needs', line2: 'a little care.' },
    'Unhealthy for All Groups': { line1: 'Your home needs', line2: 'attention.' },
    'Very Unhealthy': { line1: 'Your home needs', line2: 'action now.' },
};

const statusHeadline = computed(() => STATUS_HEADLINES[snapshot.value.status] ?? { line1: 'Your home', line2: 'is being monitored.' });
```

Everything else in `<script setup>` (props, `useLatestReading`, `useReadingHistory`, `greeting`, `hasAnyReading`, `formatUpdatedAt`, `metrics`, `scaleTicks`, `indexFillScale`, `RECOMMENDATION_ICONS`, `STATUS_DESCRIPTIONS`/`statusDescription`) stays exactly as it is today — none of that is presentation-only, it's the real data pipeline.

- [ ] **Step 4: Update the `<style scoped>` block**

Keep `.airzen-live-dot`, `.airzen-index-fill`, `.airzen-section` and their keyframes exactly as they are today (they're already brand-neutral: they only reference colors via `:style` bindings or don't reference color at all). No changes needed here.

- [ ] **Step 5: Run the backend contract test again, then build**

Run: `php artisan test --filter=DashboardPropsTest`
Expected: PASS (2 tests) — confirms the template rewrite didn't touch the props contract.

Run: `npm run build`
Expected: no TypeScript/Vue compile errors.

- [ ] **Step 6: Verify live in browser with real data**

Start `php artisan serve`, log in, open `/dashboard`. Confirm: cream background, dark-green big card with lime AQI number and two-line Fraunces headline (using real `snapshot.status`, e.g. "Your home feels clear." when status is Good), Recommendations panel restyled but showing the same real recommendation text as before, Breakdown panel's index bar/sparkline/metrics list restyled but showing real live values, History panel's weekly bars unchanged in behavior. POST a test reading via `curl` against `/iot.php` with a different AQI band and confirm the dashboard still updates live within 8 seconds (the polling composables are untouched, so this should just work, but confirm it).

- [ ] **Step 7: Run the impeccable detector**

Run: `node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json resources/js/pages/Dashboard.vue`
Expected: `[]`.

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/Dashboard.vue
git commit -m "style: rebrand Dashboard.vue to match Welcome.vue's AirZen v2 look"
```

---

## Self-Review Notes

- **Spec coverage:** Task 1 covers "add live charts of getting up and down" on the 5 sensor cards. Task 2 covers "animate a real air something" on the hero card. Task 3 covers "make the dashboard similar design to the welcome vue." All three explicit asks from the request are covered.
- **Placeholder scan:** every step above has complete, pasteable code; no "TBD"/"add appropriate styling" language.
- **Type consistency:** `MiniSparkline`'s `points: number[]` prop matches the `series: number[]` field added to `OverviewPreview.vue`'s `Metric` interface in the same task. `Dashboard.vue`'s `statusHeadline` computed keys match exactly the 5 real `RecommendationService` status strings already used by the existing `STATUS_DESCRIPTIONS` map in the same file (verified against `app/Services/RecommendationService.php`'s band labels: Good, Normal, Unhealthy for Sensitive Groups, Unhealthy for All Groups, Very Unhealthy).
