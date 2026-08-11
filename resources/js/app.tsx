import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { initializeTheme } from "@/hooks/use-appearance";
import { HorizonLayout } from "@/layouts/horizon-layout";
import "../css/inertia.css";

function cspNonce() {
    return document.querySelector<HTMLMetaElement>('meta[name="csp-nonce"]')?.content;
}

void createInertiaApp({
    nonce: cspNonce(),
    strictMode: true,
    progress: {
        color: "var(--primary)",
    },
    layout: () => HorizonLayout,
    resolve: (name) =>
        resolvePageComponent<ResolvedComponent>(
            `./pages/${name}.tsx`,
            import.meta.glob<ResolvedComponent>("./pages/**/*.tsx", {
                import: "default",
            }),
        ),
    withApp(app) {
        return (
            <TooltipProvider>
                <Toaster />
                {app}
            </TooltipProvider>
        );
    },
});

initializeTheme();
