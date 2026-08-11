import { MonitorIcon, MoonIcon, SunIcon } from "lucide-react";

import { useAppearance, type Appearance } from "@/hooks/use-appearance";

const appearanceDetails = {
    system: { icon: MonitorIcon, next: "dark" },
    dark: { icon: MoonIcon, next: "light" },
    light: { icon: SunIcon, next: "system" },
} satisfies Record<Appearance, { icon: typeof MonitorIcon; next: Appearance }>;

export function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();
    const details = appearanceDetails[appearance];
    const Icon = details.icon;
    const label = `Color scheme: ${appearance}. Switch to ${details.next}.`;

    return (
        <button
            type="button"
            className="flex h-9 w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 text-sm text-sidebar-foreground transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring"
            aria-label={label}
            onClick={() => updateAppearance(details.next)}
        >
            <Icon className="size-4 text-muted-foreground" aria-hidden="true" />
            <span>Appearance</span>
        </button>
    );
}
