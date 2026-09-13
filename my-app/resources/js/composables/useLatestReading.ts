import { onMounted, onUnmounted, ref, type Ref } from 'vue';

export interface Reading {
    id: number;
    temperature: number;
    humidity: number;
    co: number;
    nitrogen: number;
    pm25: number;
    aqi: number;
    status: string;
    color: string;
    device_id: string | null;
    created_at: string;
}

export interface Recommendation {
    status: string;
    color: string;
    recommendations: string[];
}

export function useLatestReading(
    initialReading: Reading | null,
    initialRecommendation: Recommendation | null,
    intervalMs = 8000,
): { reading: Ref<Reading | null>; recommendation: Ref<Recommendation | null> } {
    const reading = ref<Reading | null>(initialReading);
    const recommendation = ref<Recommendation | null>(initialRecommendation);
    let timer: ReturnType<typeof setInterval> | undefined;

    async function poll(): Promise<void> {
        try {
            const response = await fetch('/api/readings/latest', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            reading.value = body.reading;
            recommendation.value = body.recommendation;
        } catch {
            // network hiccup - keep showing the last known reading, try again next tick
        }
    }

    onMounted(() => {
        timer = setInterval(poll, intervalMs);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { reading, recommendation };
}
