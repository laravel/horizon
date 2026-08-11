const fullCountFormatter = new Intl.NumberFormat();
const compactCountFormatter = new Intl.NumberFormat(undefined, {
    notation: "compact",
    compactDisplay: "short",
    maximumSignificantDigits: 4,
});

export function formatCount(count: number): string {
    return Math.abs(count) >= 10_000
        ? compactCountFormatter.format(count)
        : fullCountFormatter.format(count);
}
