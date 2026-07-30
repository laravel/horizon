import { router, useHttp } from "@inertiajs/react";
import { LoaderCircleIcon, RotateCcwIcon } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { ActionMenuTrigger } from "@/components/ui/action-menu-trigger";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { retry as retryBatch } from "@/generated/routes/horizon/jobs-batches";
import { resolveHorizonRoute } from "@/lib/horizon-route";

export function BatchActions({
    batchId,
    horizonBaseUrl,
}: {
    batchId: string;
    horizonBaseUrl: string;
}) {
    const [retryDialogOpen, setRetryDialogOpen] = useState(false);
    const retryRequest = useHttp(
        "post",
        resolveHorizonRoute(retryBatch(batchId), horizonBaseUrl).url,
        {},
    );
    const working = retryRequest.processing;

    async function retryFailedJobs() {
        try {
            await retryRequest.submit({
                onSuccess: () => {
                    setRetryDialogOpen(false);
                    toast.success("Failed jobs were queued for retry.");
                    window.setTimeout(() => {
                        router.reload({
                            only: ["batch", "failedJobs"],
                        });
                    }, 3000);
                },
                onError: showError,
                onHttpException: showError,
                onNetworkError: showError,
            });
        } catch {
            // The request callbacks already present the actionable failure.
        }
    }

    function showError() {
        toast.error("The batch could not be retried.");
    }

    return (
        <>
            <DropdownMenu>
                <ActionMenuTrigger label="Batch actions" working={working} />
                <DropdownMenuContent align="end" className="w-52">
                    <DropdownMenuItem onSelect={() => setRetryDialogOpen(true)}>
                        <RotateCcwIcon className="size-3.5" />
                        Retry all failed jobs
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog open={retryDialogOpen} onOpenChange={setRetryDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Retry all failed jobs?</DialogTitle>
                        <DialogDescription>
                            Retry every failed job retained for this batch. Jobs that fail again
                            will return to the failed jobs list.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose
                            render={<Button type="button" variant="ghost" disabled={working} />}
                        >
                            Cancel
                        </DialogClose>
                        <Button
                            type="button"
                            disabled={working}
                            onClick={() => void retryFailedJobs()}
                        >
                            {working ? (
                                <LoaderCircleIcon className="animate-spin" />
                            ) : (
                                <RotateCcwIcon />
                            )}
                            {working ? "Retrying…" : "Retry all failed jobs"}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
