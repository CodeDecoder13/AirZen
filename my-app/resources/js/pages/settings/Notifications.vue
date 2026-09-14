<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    dailyDigestEnabled: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: '/settings/notifications',
    },
];

const form = useForm({
    daily_digest_enabled: props.dailyDigestEnabled,
});

const submit = () => {
    form.patch(route('notifications.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notification settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Notifications" description="Choose what AirZen emails you" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex items-start gap-3">
                        <Checkbox id="daily_digest_enabled" v-model:checked="form.daily_digest_enabled" class="mt-0.5" />
                        <Label for="daily_digest_enabled" class="flex flex-col gap-1 font-normal">
                            <span class="font-medium">Daily morning digest</span>
                            <span class="text-sm text-muted-foreground">
                                Every morning at 7:00, get an email with today's average AQI, all sensor parameters
                                (Temperature/Humidity, PM2.5, Nitrogen, Carbon Monoxide), and a recommendation. Skipped
                                automatically on days with no readings yet.
                            </span>
                        </Label>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
