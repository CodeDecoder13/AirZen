import { Leaf, ShieldCheck, Wind, type LucideIcon } from 'lucide-vue-next';

export const STATUS_HEADLINES: Record<string, { line1: string; line2: string }> = {
    Good: { line1: 'Your home feels', line2: 'clear.' },
    Normal: { line1: 'Your home feels', line2: 'steady.' },
    'Unhealthy for Sensitive Groups': { line1: 'Your home needs', line2: 'a little care.' },
    'Unhealthy for All Groups': { line1: 'Your home needs', line2: 'attention.' },
    'Very Unhealthy': { line1: 'Your home needs', line2: 'action now.' },
};

export const STATUS_DESCRIPTIONS: Record<string, string> = {
    Good: 'Air quality is comfortable and healthy. No immediate action needed.',
    Normal: 'Air quality is acceptable. Light ventilation is still a good idea.',
    'Unhealthy for Sensitive Groups': 'Sensitive individuals may notice mild effects. Consider the tips below.',
    'Unhealthy for All Groups': 'Air quality is degraded for everyone. Follow the recommendations below.',
    'Very Unhealthy': 'Air quality is poor. Take action to improve ventilation and filtration now.',
};

export const RECOMMENDATION_ICONS: LucideIcon[] = [Leaf, Wind, ShieldCheck];

export function formatUpdatedAt(iso: string | null): string {
    if (!iso) return 'No readings yet';

    const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
    if (seconds < 60) return 'Updated just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `Updated ${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    return `Updated ${hours}h ago`;
}
