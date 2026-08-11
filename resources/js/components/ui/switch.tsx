import { Switch as SwitchPrimitive } from "@base-ui/react/switch";

import { cn } from "@/lib/utils";

function Switch({ className, ...props }: SwitchPrimitive.Root.Props) {
    return (
        <SwitchPrimitive.Root
            className={cn(
                "group relative inline-flex h-[18.4px] w-8 shrink-0 items-center rounded-full border border-transparent bg-input transition-all outline-none after:absolute after:-inset-x-3 after:-inset-y-2 focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 data-checked:bg-primary data-disabled:cursor-not-allowed data-disabled:opacity-50",
                className,
            )}
            {...props}
        >
            <SwitchPrimitive.Thumb className="pointer-events-none block size-4 translate-x-0 rounded-full bg-background transition-transform group-data-checked:translate-x-[calc(100%-2px)] dark:bg-foreground" />
        </SwitchPrimitive.Root>
    );
}

export { Switch };
