<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { CalendarClock, Mail, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Recipient {
    id: number;
    email: string;
}

interface Schedule {
    enabled: boolean;
    mode: 'daily' | 'interval';
    interval_minutes: number;
    send_at: string;
    last_sent_at: string | null;
    next_run_at: string | null;
    timezone: string;
    min_interval: number;
}

const props = defineProps<{
    recipients: Recipient[];
    status?: string | null;
    schedule: Schedule;
}>();

// --- Delivery schedule -------------------------------------------------
const PRESETS = [
    { label: '15 min', minutes: 15 },
    { label: '30 min', minutes: 30 },
    { label: '1 hour', minutes: 60 },
    { label: '3 hours', minutes: 180 },
    { label: '6 hours', minutes: 360 },
    { label: '12 hours', minutes: 720 },
];

const startsAsHours = props.schedule.interval_minutes % 60 === 0;
const unit = ref<'minutes' | 'hours'>(startsAsHours ? 'hours' : 'minutes');
const amount = ref(startsAsHours ? props.schedule.interval_minutes / 60 : props.schedule.interval_minutes);

const scheduleForm = useForm({
    enabled: props.schedule.enabled,
    mode: props.schedule.mode,
    interval_minutes: props.schedule.interval_minutes,
    send_at: props.schedule.send_at,
});

const intervalMinutes = computed(() => Math.round(Number(amount.value) * (unit.value === 'hours' ? 60 : 1)));
const intervalTooShort = computed(() => scheduleForm.mode === 'interval' && intervalMinutes.value < props.schedule.min_interval);

function pickPreset(minutes: number) {
    const hours = minutes % 60 === 0;
    unit.value = hours ? 'hours' : 'minutes';
    amount.value = hours ? minutes / 60 : minutes;
}

function formatWhen(iso: string | null): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: props.schedule.timezone,
    }).format(new Date(iso));
}

const summary = computed(() => {
    if (!scheduleForm.enabled) return 'Automatic emails are paused.';
    if (scheduleForm.mode === 'daily') return `One email every day at ${scheduleForm.send_at}.`;
    const m = intervalMinutes.value;
    return m % 60 === 0 ? `An email every ${m / 60 === 1 ? 'hour' : `${m / 60} hours`}.` : `An email every ${m} minutes.`;
});

function saveSchedule() {
    scheduleForm.interval_minutes = intervalMinutes.value;
    scheduleForm.put(route('notifications.schedule'), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];

const form = useForm({ email: '' });
const sendForm = useForm({});

function sendNow() {
    sendForm.post(route('notifications.send'), { preserveScroll: true });
}

function submit() {
    form.post(route('notifications.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function remove(id: number) {
    form.delete(route('notifications.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="az2-display text-2xl text-[#1D352D]">Notifications</h1>
                    <p class="text-sm text-[#6B8577]">Everyone in the list below gets the air quality digest on the schedule you set.</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <button
                        type="button"
                        :disabled="sendForm.processing || recipients.length === 0"
                        class="flex items-center gap-1.5 rounded-full bg-[#1A3C30] px-5 py-2 text-sm font-bold text-[#C6DF58] hover:bg-[#12291f] disabled:opacity-60"
                        @click="sendNow"
                    >
                        <Send :size="15" />
                        {{ sendForm.processing ? 'Sending…' : 'Send air quality email now' }}
                    </button>
                    <p v-if="sendForm.errors.send" class="text-xs text-red-600">{{ sendForm.errors.send }}</p>
                    <p v-else-if="status" class="text-xs text-[#2A8362]">{{ status }}</p>
                </div>
            </div>

            <!-- Delivery schedule -->
            <section
                class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-[#1A3C30] via-[#17352A] to-[#0F241C] p-6 text-white shadow-[0_24px_48px_-24px_rgba(15,36,28,0.7)] md:p-8"
            >
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[#C6DF58]/10 blur-3xl"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#C6DF58] text-[#1A3C30]">
                            <CalendarClock :size="22" />
                        </span>
                        <div>
                            <h2 class="az2-display text-xl text-white">Delivery schedule</h2>
                            <p class="text-sm text-[#9DB8A9]">{{ summary }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        role="switch"
                        :aria-checked="scheduleForm.enabled"
                        class="flex items-center gap-3 rounded-full bg-white/10 py-1.5 pl-4 pr-1.5 text-sm font-semibold text-white"
                        @click="scheduleForm.enabled = !scheduleForm.enabled"
                    >
                        {{ scheduleForm.enabled ? 'Active' : 'Paused' }}
                        <span class="relative h-7 w-12 rounded-full transition-colors" :class="scheduleForm.enabled ? 'bg-[#C6DF58]' : 'bg-white/20'">
                            <span
                                class="absolute top-1 h-5 w-5 rounded-full bg-[#1A3C30] transition-all"
                                :class="scheduleForm.enabled ? 'left-6' : 'left-1 bg-white'"
                            ></span>
                        </span>
                    </button>
                </div>

                <div class="relative mt-6 space-y-5" :class="{ 'pointer-events-none opacity-40': !scheduleForm.enabled }">
                    <!-- Mode -->
                    <div class="inline-flex rounded-full bg-white/10 p-1 text-sm font-bold">
                        <button
                            v-for="m in [
                                { key: 'interval', label: 'Repeat every' },
                                { key: 'daily', label: 'Daily at' },
                            ]"
                            :key="m.key"
                            type="button"
                            class="rounded-full px-5 py-2 transition-colors"
                            :class="scheduleForm.mode === m.key ? 'bg-[#C6DF58] text-[#1A3C30]' : 'text-[#9DB8A9] hover:text-white'"
                            @click="scheduleForm.mode = m.key as 'daily' | 'interval'"
                        >
                            {{ m.label }}
                        </button>
                    </div>

                    <!-- Interval -->
                    <div v-if="scheduleForm.mode === 'interval'" class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="p in PRESETS"
                                :key="p.minutes"
                                type="button"
                                class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors"
                                :class="
                                    intervalMinutes === p.minutes
                                        ? 'border-[#C6DF58] bg-[#C6DF58]/15 text-[#C6DF58]'
                                        : 'border-white/15 text-[#CFE0D5] hover:border-white/40'
                                "
                                @click="pickPreset(p.minutes)"
                            >
                                {{ p.label }}
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-[#9DB8A9]">Custom</span>
                            <input
                                v-model.number="amount"
                                type="number"
                                min="1"
                                class="w-24 rounded-[10px] border border-white/15 bg-white/10 px-3 py-2 text-sm font-semibold text-white focus:border-[#C6DF58] focus:outline-none"
                            />
                            <select
                                v-model="unit"
                                class="rounded-[10px] border border-white/15 bg-[#1A3C30] px-3 py-2 text-sm font-semibold text-white focus:border-[#C6DF58] focus:outline-none"
                            >
                                <option value="minutes">minutes</option>
                                <option value="hours">hours</option>
                            </select>
                        </div>
                        <p v-if="intervalTooShort" class="text-xs text-amber-300">Minimum is every {{ schedule.min_interval }} minutes.</p>
                        <p v-if="scheduleForm.errors.interval_minutes" class="text-xs text-amber-300">{{ scheduleForm.errors.interval_minutes }}</p>
                    </div>

                    <!-- Daily -->
                    <div v-else class="flex items-center gap-3">
                        <input
                            v-model="scheduleForm.send_at"
                            type="time"
                            class="rounded-[10px] border border-white/15 bg-white/10 px-4 py-2.5 text-lg font-semibold text-white [color-scheme:dark] focus:border-[#C6DF58] focus:outline-none"
                        />
                        <span class="text-sm text-[#9DB8A9]">{{ schedule.timezone }} time</span>
                        <p v-if="scheduleForm.errors.send_at" class="text-xs text-amber-300">{{ scheduleForm.errors.send_at }}</p>
                    </div>
                </div>

                <div class="relative mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-5">
                    <div class="flex gap-8 text-sm">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#6B8577]">Next email</p>
                            <p class="font-semibold text-white">{{ schedule.enabled ? formatWhen(schedule.next_run_at) : 'Paused' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#6B8577]">Last sent</p>
                            <p class="font-semibold text-white">{{ formatWhen(schedule.last_sent_at) }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        :disabled="scheduleForm.processing || intervalTooShort"
                        class="rounded-full bg-[#C6DF58] px-6 py-2.5 text-sm font-bold text-[#1A3C30] hover:bg-[#d6ec6b] disabled:opacity-50"
                        @click="saveSchedule"
                    >
                        {{ scheduleForm.processing ? 'Saving…' : 'Save schedule' }}
                    </button>
                </div>
            </section>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                <form @submit.prevent="submit" class="flex flex-wrap items-start gap-3">
                    <div class="flex-1">
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="name@example.com"
                            class="w-full rounded-[10px] border border-[#E4EAE0] bg-[#F7F8F1] px-3 py-2 text-sm text-[#1D352D] focus:border-[#2A8362] focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-1.5 rounded-full bg-[#2A8362] px-5 py-2 text-sm font-bold text-white hover:bg-[#226b4f] disabled:opacity-60"
                    >
                        <Mail :size="15" />
                        Add recipient
                    </button>
                </form>

                <table class="mt-6 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#EEF1E9] text-[11px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">
                            <th class="pb-2">Email</th>
                            <th class="w-16 pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF1E9]">
                        <tr v-for="recipient in recipients" :key="recipient.id">
                            <td class="py-3 text-[#1D352D]">{{ recipient.email }}</td>
                            <td class="py-3 text-right">
                                <button type="button" class="text-[#6B8577] hover:text-red-600" @click="remove(recipient.id)">
                                    <Trash2 :size="16" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="recipients.length === 0">
                            <td colspan="2" class="py-6 text-center text-[#6B8577]">No recipients yet. Add one above.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
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
