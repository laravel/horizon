import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
} from "lucide-react";
import { Toaster as Sonner, type ToasterProps } from "sonner";

import { useAppearance, type ResolvedAppearance } from "@/hooks/use-appearance";

function inverseAppearance(appearance: ResolvedAppearance): ResolvedAppearance {
    return appearance === "dark" ? "light" : "dark";
}

const Toaster = ({ ...props }: ToasterProps) => {
    const { resolvedAppearance } = useAppearance();
    const theme = inverseAppearance(resolvedAppearance);

    return (
        <Sonner
            theme={theme}
            position="bottom-center"
            className="toaster group"
            icons={{
                success: <CircleCheckIcon className="size-4 text-ok" />,
                info: <InfoIcon className="size-4" />,
                warning: <TriangleAlertIcon className="size-4" />,
                error: <OctagonXIcon className="size-4 text-error" />,
                loading: <Loader2Icon className="size-4 animate-spin" />,
            }}
            toastOptions={{
                classNames: {
                    toast: "cn-toast !py-2.5",
                },
            }}
            {...props}
        />
    );
};

export { Toaster };
