<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Trash2 } from 'lucide-vue-next';

interface Recipient {
    id: number;
    email: string;
}

defineProps<{
    recipients: Recipient[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];

const form = useForm({ email: '' });

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
            <div>
                <h1 class="az2-display text-2xl text-[#1D352D]">Notifications</h1>
                <p class="text-sm text-[#6B8577]">Everyone in this table gets the 7:00 AM morning air quality digest.</p>
            </div>

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
