import { useHttp } from "@inertiajs/react";
import { LoaderCircleIcon, RotateCcwIcon } from "lucide-react";
import { toast } from "sonner";

import { ActionMenuTrigger } from "@/components/ui/action-menu-trigger";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { show as retryJob } from "@/generated/routes/horizon/retry-jobs";
import { resolveHorizonRoute } from "@/lib/horizon-route";

export function FailedJobActionsMenu({
    jobId,
    horizonBaseUrl,
    label,
    onSuccess,
}: {
    jobId: string;
    horizonBaseUrl: string;
    label: string;
    onSuccess?: () => void;
}) {
    const request = useHttp("post", resolveHorizonRoute(retryJob(jobId), horizonBaseUrl).url, {});
    const working = request.processing;

    async function retry() {
        try {
            await request.submit({
                onSuccess: () => {
                    toast.success(`${label} was queued for retry.`);
                    onSuccess?.();
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
        toast.error(`${label} could not be retried.`);
    }

    return (
        <DropdownMenu>
            <ActionMenuTrigger label="Retry failed job" working={working} />
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuItem disabled={working} onSelect={() => void retry()}>
                    {working ? (
                        <LoaderCircleIcon className="size-3.5 animate-spin" />
                    ) : (
                        <RotateCcwIcon className="size-3.5" />
                    )}
                    Retry
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
