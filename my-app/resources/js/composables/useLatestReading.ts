import { onMounted, onUnmounted, ref, type Ref } from 'vue';

export interface SensorReadings {
    temperature: number | null;
    humidity: number | null;
    nitrogen: number | null;
    co: number | null;
    particulate_matter: number | null;
}

export interface ReadingSnapshot {
    readings: SensorReadings;
    aqi: number;
    status: string;
    color: string;
    recommendations: string[];
    updated_at: string | null;
}

export function useLatestReading(
    initialSnapshot: ReadingSnapshot,
    intervalMs = 8000,
    endpoint = '/api/readings/latest',
): { snapshot: Ref<ReadingSnapshot> } {
    const snapshot = ref<ReadingSnapshot>(initialSnapshot) as Ref<ReadingSnapshot>;
    let timer: ReturnType<typeof setInterval> | undefined;

    async function poll(): Promise<void> {
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            snapshot.value = await response.json();
        } catch {
            // network hiccup - keep showing the last known snapshot, try again next tick
        }
    }

    onMounted(() => {
        timer = setInterval(poll, intervalMs);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { snapshot };
}
