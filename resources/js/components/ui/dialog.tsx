import { Dialog as DialogPrimitive } from "@base-ui/react/dialog";
import { XIcon } from "lucide-react";
import type { ComponentProps } from "react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

function Dialog(props: DialogPrimitive.Root.Props) {
    return <DialogPrimitive.Root {...props} />;
}

function DialogClose(props: DialogPrimitive.Close.Props) {
    return <DialogPrimitive.Close {...props} />;
}

function DialogContent({ className, children, ...props }: DialogPrimitive.Popup.Props) {
    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Backdrop className="fixed inset-0 isolate z-50 bg-black/10 backdrop-blur-xs" />
            <DialogPrimitive.Popup
                className={cn(
                    "fixed top-1/2 left-1/2 z-50 grid w-full max-w-sm -translate-x-1/2 -translate-y-1/2 gap-4 rounded-xl bg-popover p-4 text-sm text-popover-foreground shadow-lg ring-1 ring-foreground/10 outline-none",
                    className,
                )}
                {...props}
            >
                {children}
                <DialogPrimitive.Close
                    render={
                        <Button variant="ghost" className="absolute top-2 right-2" size="icon-sm" />
                    }
                >
                    <XIcon />
                    <span className="sr-only">Close</span>
                </DialogPrimitive.Close>
            </DialogPrimitive.Popup>
        </DialogPrimitive.Portal>
    );
}

function DialogHeader({ className, ...props }: ComponentProps<"div">) {
    return <div className={cn("flex flex-col gap-2", className)} {...props} />;
}

function DialogFooter({ className, ...props }: ComponentProps<"div">) {
    return (
        <div
            className={cn(
                "-mx-4 -mb-4 mt-2 flex justify-end gap-2 rounded-b-xl border-t bg-muted/50 p-4",
                className,
            )}
            {...props}
        />
    );
}

function DialogTitle({ className, ...props }: DialogPrimitive.Title.Props) {
    return (
        <DialogPrimitive.Title
            className={cn("font-heading text-base leading-none font-medium", className)}
            {...props}
        />
    );
}

function DialogDescription({ className, ...props }: DialogPrimitive.Description.Props) {
    return (
        <DialogPrimitive.Description
            className={cn("text-sm text-muted-foreground", className)}
            {...props}
        />
    );
}

export {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
};
