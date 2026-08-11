import { Button as ButtonPrimitive } from "@base-ui/react/button";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
    "group/button inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border border-transparent bg-clip-padding text-sm font-medium whitespace-nowrap transition-all outline-none select-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 active:not-aria-[haspopup]:translate-y-px disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
    {
        variants: {
            variant: {
                default:
                    "border-action-primary bg-action-primary text-white shadow-action hover:border-action-primary-hover hover:bg-action-primary-hover",
                outline:
                    "border-border bg-background hover:bg-muted hover:text-foreground aria-expanded:bg-muted aria-expanded:text-foreground dark:border-input dark:bg-input/30 dark:hover:bg-input/50",
                secondary:
                    "bg-secondary text-secondary-foreground hover:bg-[color-mix(in_oklch,var(--secondary),var(--foreground)_5%)] aria-expanded:bg-secondary aria-expanded:text-secondary-foreground",
                code: "border-code-control-border bg-code-control text-code-control-foreground hover:border-code-control-hover-border hover:bg-code-control-hover hover:text-code-control-hover-foreground focus-visible:border-code-control-foreground focus-visible:ring-code-control-foreground/60 aria-expanded:border-code-control-hover-border aria-expanded:bg-code-control-hover aria-expanded:text-code-control-hover-foreground",
                ghost: "hover:bg-muted hover:text-foreground aria-expanded:bg-muted aria-expanded:text-foreground in-data-[clickable-row]:hover:bg-table-row-action-hover in-data-[clickable-row]:aria-expanded:bg-table-row-action-hover dark:hover:bg-muted/50",
                action: "hover:bg-table-row-action-hover hover:text-primary aria-expanded:bg-table-row-action-hover aria-expanded:text-primary",
                destructive:
                    "bg-destructive-action/10 text-destructive-action hover:bg-destructive-action/20 focus-visible:border-destructive-action/40 focus-visible:ring-destructive-action/20 dark:bg-destructive-action/20 dark:hover:bg-destructive-action/30 dark:focus-visible:ring-destructive-action/40",
                link: "text-primary underline-offset-4 hover:underline",
            },
            size: {
                default:
                    "h-[34px] gap-2 px-[13px] text-[13.5px] has-data-[icon=inline-end]:pr-[11px] has-data-[icon=inline-start]:pl-[11px]",
                xs: "h-6 gap-1 rounded-[min(var(--radius-md),10px)] px-[7px] text-xs in-data-[slot=button-group]:rounded-lg has-data-[icon=inline-end]:pr-[5px] has-data-[icon=inline-start]:pl-[5px] [&_svg:not([class*='size-'])]:size-3",
                sm: "h-7 gap-1 rounded-[min(var(--radius-md),12px)] px-[9px] text-[0.8rem] in-data-[slot=button-group]:rounded-lg has-data-[icon=inline-end]:pr-[5px] has-data-[icon=inline-start]:pl-[5px] [&_svg:not([class*='size-'])]:size-3.5",
                lg: "h-9 gap-1.5 px-[9px] has-data-[icon=inline-end]:pr-[7px] has-data-[icon=inline-start]:pl-[7px]",
                icon: "size-8",
                "icon-xs":
                    "size-6 rounded-[min(var(--radius-md),10px)] in-data-[slot=button-group]:rounded-lg [&_svg:not([class*='size-'])]:size-3",
                "icon-sm":
                    "size-7 rounded-[min(var(--radius-md),12px)] in-data-[slot=button-group]:rounded-lg",
                "icon-lg": "size-9",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    },
);

function Button({
    className,
    variant = "default",
    size = "default",
    ...props
}: ButtonPrimitive.Props & VariantProps<typeof buttonVariants>) {
    return (
        <ButtonPrimitive
            data-slot="button"
            data-variant={variant}
            data-size={size}
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        />
    );
}

export { Button, buttonVariants };
