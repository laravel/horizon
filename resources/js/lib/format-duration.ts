const numberFormatter = new Intl.NumberFormat("en-US");
const preciseNumberFormatter = new Intl.NumberFormat("en-US", {
    maximumFractionDigits: 2,
});

const preciseUnits = [
    { suffix: "y", seconds: 31_536_000 },
    { suffix: "mo", seconds: 2_592_000 },
    { suffix: "d", seconds: 86_400 },
    { suffix: "h", seconds: 3_600 },
    { suffix: "m", seconds: 60 },
    { suffix: "s", seconds: 1 },
] as const;

export function formatDuration(seconds: number, precise = false): string {
    const normalized = Number.isFinite(seconds) ? Math.max(0, seconds) : 0;

    if (normalized > 0 && normalized < 1) {
        return `${preciseNumberFormatter.format(normalized * 1_000)}ms`;
    }

    const duration = Math.round(normalized * 100) / 100;

    if (precise) {
        let remaining = duration;
        const parts: string[] = [];

        for (const unit of preciseUnits) {
            if (parts.length === 3) {
                break;
            }

            const value = unit.seconds === 1 ? remaining : Math.floor(remaining / unit.seconds);

            if (value <= 0) {
                continue;
            }

            parts.push(`${preciseNumberFormatter.format(value)}${unit.suffix}`);
            remaining -= value * unit.seconds;
        }

        return parts.length > 0 ? parts.join(" ") : "0s";
    }

    if (duration <= 5) {
        return `${preciseNumberFormatter.format(duration)}s`;
    }

    if (duration < 60) {
        return `${numberFormatter.format(Math.round(duration))}s`;
    }

    if (duration < 90) {
        return "1m";
    }

    if (duration < 2_700) {
        return `${numberFormatter.format(Math.round(duration / 60))}m`;
    }

    if (duration < 5_400) {
        return "1h";
    }

    if (duration < 79_200) {
        return `${numberFormatter.format(Math.round(duration / 3_600))}h`;
    }

    if (duration < 129_600) {
        return "1d";
    }

    return `${numberFormatter.format(Math.round(duration / 86_400))}d`;
}

export function formatWaitDuration(seconds: number): string {
    const value = Math.max(0, Math.floor(Number.isFinite(seconds) ? seconds : 0));

    if (value === 0) {
        return "Sub-second";
    }

    if (value < 60) {
        return `${value}s`;
    }

    if (value < 3_600) {
        const minutes = Math.floor(value / 60);
        const remainingSeconds = value % 60;

        return remainingSeconds ? `${minutes}m ${remainingSeconds}s` : `${minutes}m`;
    }

    if (value < 86_400) {
        const hours = Math.floor(value / 3_600);
        const remainingMinutes = Math.floor((value % 3_600) / 60);

        return remainingMinutes ? `${hours}h ${remainingMinutes}m` : `${hours}h`;
    }

    const days = Math.floor(value / 86_400);
    const remainingHours = Math.floor((value % 86_400) / 3_600);

    return remainingHours ? `${days}d ${remainingHours}h` : `${days}d`;
}
