import type { ComponentProps } from "react";
import { CopyIcon } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { copyToClipboard } from "@/lib/clipboard";
import { cn } from "@/lib/utils";

type CodeBlockProps = ComponentProps<"pre"> & {
    copyLabel?: string;
    copyValue?: string;
};

export function CodeBlock({
    className,
    copyLabel = "content",
    copyValue,
    ...props
}: CodeBlockProps) {
    const copy = async () => {
        if (copyValue === undefined) {
            return;
        }

        if (await copyToClipboard(copyValue)) {
            toast.success("Copied to clipboard.");

            return;
        }

        toast.error("Content could not be copied.");
    };

    return (
        <div className="relative" data-code-theme="dark">
            {copyValue !== undefined ? (
                <Tooltip>
                    <TooltipTrigger
                        render={
                            <Button
                                type="button"
                                variant="code"
                                size="icon-sm"
                                className="absolute top-3 right-3 z-10"
                                aria-label={`Copy ${copyLabel}`}
                                onClick={() => void copy()}
                            />
                        }
                    >
                        <CopyIcon aria-hidden="true" />
                    </TooltipTrigger>
                    <TooltipContent side="left">Copy {copyLabel}</TooltipContent>
                </Tooltip>
            ) : null}
            <pre
                {...props}
                className={cn(
                    "m-0 overflow-auto bg-code px-6 py-4 font-mono text-[12.5px] leading-6 font-medium whitespace-pre text-code-foreground",
                    className,
                )}
            />
        </div>
    );
}
