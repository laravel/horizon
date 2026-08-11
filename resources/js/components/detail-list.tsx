import {
    useCallback,
    useLayoutEffect,
    useRef,
    useState,
    type ComponentProps,
    type ReactNode,
} from "react";

import { cn } from "@/lib/utils";

type OverflowEdges = {
    left: boolean;
    right: boolean;
};

function DetailList({ className, ...props }: ComponentProps<"dl">) {
    return (
        <dl
            data-slot="detail-list"
            className={cn(
                "grid grid-cols-[minmax(0,1fr)_minmax(0,2fr)] items-start pt-1.5 pb-2.5 sm:grid-cols-[12rem_minmax(0,1fr)]",
                className,
            )}
            {...props}
        />
    );
}

function DetailListItem({
    label,
    children,
    bordered = false,
    scrollable = false,
    valueClassName,
    valueTestId,
    valueTitle,
}: {
    label: ReactNode;
    children: ReactNode;
    bordered?: boolean;
    scrollable?: boolean;
    valueClassName?: string;
    valueTestId?: string;
    valueTitle?: string;
}) {
    const borderClassName = bordered && "border-t border-dashed border-separator";

    return (
        <>
            <dt
                data-slot="detail-list-label"
                className={cn(
                    "min-w-0 py-2.5 pr-2 pl-4 text-muted-foreground sm:pl-6",
                    borderClassName,
                )}
            >
                {label}
            </dt>
            <dd
                data-slot="detail-list-value"
                data-test={valueTestId}
                className={cn("min-w-0 py-2.5 pr-4 pl-2 sm:pr-6", borderClassName, valueClassName)}
                title={valueTitle}
            >
                {scrollable ? (
                    <ScrollableDetailValue label={typeof label === "string" ? label : undefined}>
                        {children}
                    </ScrollableDetailValue>
                ) : (
                    children
                )}
            </dd>
        </>
    );
}

function ScrollableDetailValue({ children, label }: { children: ReactNode; label?: string }) {
    const viewportRef = useRef<HTMLDivElement>(null);
    const contentRef = useRef<HTMLSpanElement>(null);
    const [overflowEdges, setOverflowEdges] = useState<OverflowEdges>({
        left: false,
        right: false,
    });

    const updateOverflowEdges = useCallback(() => {
        const viewport = viewportRef.current;

        if (!viewport) {
            return;
        }

        const maximumScrollLeft = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
        const scrollLeft = Math.min(maximumScrollLeft, Math.max(0, viewport.scrollLeft));
        const nextOverflowEdges = {
            left: scrollLeft > 1,
            right: maximumScrollLeft - scrollLeft > 1,
        };

        setOverflowEdges((currentOverflowEdges) => {
            if (
                currentOverflowEdges.left === nextOverflowEdges.left &&
                currentOverflowEdges.right === nextOverflowEdges.right
            ) {
                return currentOverflowEdges;
            }

            return nextOverflowEdges;
        });
    }, []);

    useLayoutEffect(() => {
        const viewport = viewportRef.current;
        const content = contentRef.current;

        if (!viewport || !content) {
            return;
        }

        let animationFrame: number | null = null;
        const scheduleUpdate = () => {
            if (animationFrame !== null) {
                return;
            }

            animationFrame = window.requestAnimationFrame(() => {
                animationFrame = null;
                updateOverflowEdges();
            });
        };

        updateOverflowEdges();
        viewport.addEventListener("scroll", scheduleUpdate, { passive: true });

        const resizeObserver =
            typeof ResizeObserver === "undefined" ? null : new ResizeObserver(scheduleUpdate);

        resizeObserver?.observe(viewport);
        resizeObserver?.observe(content);

        return () => {
            viewport.removeEventListener("scroll", scheduleUpdate);
            resizeObserver?.disconnect();

            if (animationFrame !== null) {
                window.cancelAnimationFrame(animationFrame);
            }
        };
    }, [updateOverflowEdges]);

    const hasOverflow = overflowEdges.left || overflowEdges.right;

    return (
        <div
            className="relative min-w-0"
            data-overflow-left={overflowEdges.left}
            data-overflow-right={overflowEdges.right}
            data-slot="detail-list-value-overflow"
        >
            <div
                ref={viewportRef}
                aria-label={label ? `${label} value` : "Scrollable value"}
                className="overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                data-slot="detail-list-value-scroll"
                role={hasOverflow ? "region" : undefined}
                tabIndex={hasOverflow ? 0 : undefined}
            >
                <span ref={contentRef} className="inline-block min-w-max">
                    {children}
                </span>
            </div>
            <span
                aria-hidden="true"
                className={cn(
                    "pointer-events-none absolute inset-y-0 left-0 z-10 w-10 bg-linear-to-r from-card to-transparent transition-opacity duration-150 motion-reduce:transition-none",
                    overflowEdges.left ? "opacity-100" : "opacity-0",
                )}
                data-slot="detail-list-value-fade-left"
            />
            <span
                aria-hidden="true"
                className={cn(
                    "pointer-events-none absolute inset-y-0 right-0 z-10 w-10 bg-linear-to-l from-card to-transparent transition-opacity duration-150 motion-reduce:transition-none",
                    overflowEdges.right ? "opacity-100" : "opacity-0",
                )}
                data-slot="detail-list-value-fade-right"
            />
        </div>
    );
}

export { DetailList, DetailListItem };
