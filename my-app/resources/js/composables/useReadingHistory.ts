import { onMounted, onUnmounted, ref, type Ref } from 'vue';

export interface TrendPoint {
    value: number;
    recorded_at: string;
}

export interface WeeklyDay {
    label: string;
    date: string;
    average_aqi: number | null;
    is_today: boolean;
}

export interface ReadingHistory {
    trend: TrendPoint[];
    weekly: WeeklyDay[];
}

export function useReadingHistory(initialHistory: ReadingHistory, intervalMs = 60000): { history: Ref<ReadingHistory> } {
    const history = ref<ReadingHistory>(initialHistory) as Ref<ReadingHistory>;
    let timer: ReturnType<typeof setInterval> | undefined;

    async function poll(): Promise<void> {
        try {
            const response = await fetch('/api/readings/history', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            history.value = await response.json();
        } catch {
            // network hiccup - keep showing the last known history, try again next tick
        }
    }

    onMounted(() => {
        timer = setInterval(poll, intervalMs);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { history };
}
