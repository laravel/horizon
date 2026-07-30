import { Menu as DropdownMenuPrimitive } from "@base-ui/react/menu";
import { createContext, useContext, useId, useState } from "react";

import { cn } from "@/lib/utils";

const DropdownMenuHandleContext = createContext<DropdownMenuPrimitive.Handle<unknown> | null>(null);

function DropdownMenu({ handle: externalHandle, ...props }: DropdownMenuPrimitive.Root.Props) {
    const [internalHandle] = useState(() => DropdownMenuPrimitive.createHandle());
    const handle = externalHandle ?? internalHandle;

    return (
        <DropdownMenuHandleContext.Provider value={handle}>
            <DropdownMenuPrimitive.Root handle={handle} {...props} />
        </DropdownMenuHandleContext.Provider>
    );
}

function DropdownMenuTrigger({
    id: externalId,
    handle: externalHandle,
    onClick,
    onPointerDown,
    ...props
}: DropdownMenuPrimitive.Trigger.Props) {
    const internalHandle = useContext(DropdownMenuHandleContext);
    const generatedId = useId();
    const id = externalId ?? generatedId;
    const handle = externalHandle ?? internalHandle;

    const toggle = () => {
        if (!handle) {
            return;
        }

        if (handle.isOpen) {
            handle.close();
        } else {
            handle.open(id);
        }
    };

    return (
        <DropdownMenuPrimitive.Trigger
            id={id}
            handle={handle ?? undefined}
            onPointerDown={(event) => {
                onPointerDown?.(event);

                if (!event.defaultPrevented && event.button === 0 && !event.ctrlKey && handle) {
                    event.preventDefault();
                    event.preventBaseUIHandler();
                    toggle();
                }
            }}
            onClick={(event) => {
                onClick?.(event);

                if (!event.defaultPrevented && event.detail === 0 && handle) {
                    toggle();
                }

                if (handle) {
                    event.preventBaseUIHandler();
                }
            }}
            {...props}
        />
    );
}

function DropdownMenuContent({
    align = "start",
    side = "bottom",
    sideOffset = 4,
    className,
    onClick,
    ...props
}: DropdownMenuPrimitive.Popup.Props &
    Pick<DropdownMenuPrimitive.Positioner.Props, "align" | "side" | "sideOffset">) {
    return (
        <DropdownMenuPrimitive.Portal>
            <DropdownMenuPrimitive.Positioner
                className="isolate z-50 outline-none"
                align={align}
                side={side}
                sideOffset={sideOffset}
            >
                <DropdownMenuPrimitive.Popup
                    className={cn(
                        "z-50 min-w-32 overflow-hidden rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10 outline-none",
                        className,
                    )}
                    {...props}
                    onClick={(event) => {
                        event.stopPropagation();
                        onClick?.(event);
                    }}
                />
            </DropdownMenuPrimitive.Positioner>
        </DropdownMenuPrimitive.Portal>
    );
}

function DropdownMenuItem({
    className,
    onClick,
    onSelect,
    ...props
}: Omit<DropdownMenuPrimitive.Item.Props, "onSelect"> & {
    onSelect?: React.MouseEventHandler<HTMLElement>;
}) {
    return (
        <DropdownMenuPrimitive.Item
            className={cn(
                "group relative flex cursor-default items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-foreground outline-none select-none focus:bg-dropdown-item-hover focus:text-accent-foreground [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 [&_svg]:text-muted-foreground [&_svg]:transition-colors hover:[&_svg]:text-primary",
                className,
            )}
            onClick={(event) => {
                onClick?.(event);

                if (!event.defaultPrevented) {
                    onSelect?.(event);
                }
            }}
            {...props}
        />
    );
}

function DropdownMenuSeparator({ className, ...props }: DropdownMenuPrimitive.Separator.Props) {
    return (
        <DropdownMenuPrimitive.Separator
            className={cn("-mx-1 my-1 h-px bg-border", className)}
            {...props}
        />
    );
}

export {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
};
