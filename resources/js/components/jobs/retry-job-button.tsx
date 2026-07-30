import { useHttp } from "@inertiajs/react";
import { LoaderCircleIcon, RotateCcwIcon } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { show as retryJob } from "@/generated/routes/horizon/retry-jobs";
import { resolveHorizonRoute } from "@/lib/horizon-route";

export function RetryJobButton({
    baseUrl,
    jobId,
    label,
    onSuccess,
}: {
    baseUrl: string;
    jobId: string;
    label: string;
    onSuccess?: () => void;
}) {
    const request = useHttp("post", resolveHorizonRoute(retryJob(jobId), baseUrl).url, {});

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
        <Button
            type="button"
            variant="action"
            size="icon-sm"
            className="text-muted-foreground focus-visible:text-primary"
            aria-label={`Retry ${label}`}
            disabled={request.processing}
            onClick={() => void retry()}
        >
            {request.processing ? <LoaderCircleIcon className="animate-spin" /> : <RotateCcwIcon />}
        </Button>
    );
}
