import { router, useHttp } from "@inertiajs/react";
import { PauseIcon, PlayIcon } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { ActionMenuTrigger } from "@/components/ui/action-menu-trigger";
import { Badge } from "@/components/ui/badge";
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { Switch } from "@/components/ui/switch";
import {
    destroy as resumeQueue,
    store as pauseQueue,
} from "@/generated/routes/horizon/queues/pause";
import { resolveHorizonRoute } from "@/lib/horizon-route";

const deadlineFormatter = new Intl.DateTimeFormat(undefined, {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
});

const durations = [
    { label: "15m", minutes: 15 },
    { label: "1h", minutes: 60 },
    { label: "4h", minutes: 240 },
] as const;

export function QueuePauseBadge({
    paused,
    pausedUntil,
}: {
    paused: boolean;
    pausedUntil: number | null;
}) {
    if (!paused) {
        return null;
    }

    return (
        <Badge variant="paused">
            {pausedUntil
                ? `Paused until ${deadlineFormatter.format(new Date(pausedUntil * 1000))}`
                : "Paused"}
        </Badge>
    );
}

export function QueueActionsMenu({
    connection,
    queue,
    paused,
    pausedUntil,
    horizonBaseUrl,
    queuePauseFor = false,
}: {
    connection: string;
    queue: string;
    paused: boolean;
    pausedUntil: number | null;
    horizonBaseUrl: string;
    queuePauseFor?: boolean;
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [pauseIndefinitely, setPauseIndefinitely] = useState(pausedUntil === null);
    const [durationMinutes, setDurationMinutes] = useState("15");
    const pauseRoute = resolveHorizonRoute(pauseQueue({ connection, queue }), horizonBaseUrl);
    const resumeRoute = resolveHorizonRoute(resumeQueue({ connection, queue }), horizonBaseUrl);
    const duration = Number.parseInt(durationMinutes, 10);
    const pauseRequest = useHttp<{ duration_minutes: number | null }>({
        duration_minutes: null,
    });
    const resumeRequest = useHttp({});
    const working = pauseRequest.processing || resumeRequest.processing;

    function openPauseDialog() {
        if (queuePauseFor) {
            const hasTimedPause = pausedUntil !== null;

            setPauseIndefinitely(!hasTimedPause);
            setDurationMinutes(
                hasTimedPause
                    ? String(Math.max(1, Math.ceil((pausedUntil * 1000 - Date.now()) / 60_000)))
                    : "15",
            );
        }

        setDialogOpen(true);
    }

    async function pause() {
        pauseRequest.setData(
            "duration_minutes",
            queuePauseFor && !pauseIndefinitely ? duration : null,
        );

        try {
            await pauseRequest.post(pauseRoute.url, {
                onSuccess: () => {
                    setDialogOpen(false);
                    toast.success(`${queue} paused.`);
                    router.reload({ only: ["workload"] });
                },
                onError: showError,
                onHttpException: showError,
                onNetworkError: showError,
            });
        } catch {
            // The request callbacks already present the actionable failure.
        }
    }

    async function resume() {
        try {
            await resumeRequest.delete(resumeRoute.url, {
                onSuccess: () => {
                    toast.success(`${queue} resumed.`);
                    router.reload({ only: ["workload"] });
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
        toast.error(`The ${queue} queue could not be updated.`);
    }

    const pauseIndefinitelyId = `pause-indefinitely-${connection}-${queue}`;
    const pauseDurationId = `pause-duration-${connection}-${queue}`;
    const durationInvalid =
        queuePauseFor &&
        !pauseIndefinitely &&
        (!Number.isInteger(duration) || duration < 1 || duration > 525600);

    return (
        <>
            <DropdownMenu>
                <ActionMenuTrigger label={`Queue actions for ${queue}`} working={working} />
                <DropdownMenuContent align="end" className="w-44">
                    {paused ? (
                        <>
                            <DropdownMenuItem onSelect={() => void resume()}>
                                <PlayIcon />
                                Resume now
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                        </>
                    ) : null}
                    <DropdownMenuItem onSelect={openPauseDialog}>
                        <PauseIcon />
                        {paused ? "Update pause" : "Pause"}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    {queuePauseFor ? (
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                void pause();
                            }}
                        >
                            <DialogHeader>
                                <DialogTitle>Pause {queue}</DialogTitle>
                                <DialogDescription>
                                    Choose when Horizon should resume processing this queue.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="flex flex-col gap-5 py-5">
                                <div className="flex items-center justify-between gap-4">
                                    <label className="font-medium" htmlFor={pauseIndefinitelyId}>
                                        Pause indefinitely
                                    </label>
                                    <Switch
                                        id={pauseIndefinitelyId}
                                        checked={pauseIndefinitely}
                                        onCheckedChange={setPauseIndefinitely}
                                    />
                                </div>

                                {!pauseIndefinitely ? (
                                    <div className="flex flex-col gap-2.5">
                                        <div className="flex items-center justify-between gap-4">
                                            <label
                                                className="font-medium"
                                                htmlFor={pauseDurationId}
                                            >
                                                Pause for
                                            </label>
                                            <div
                                                className="flex items-center gap-1.5 text-xs"
                                                aria-label="Pause duration presets"
                                            >
                                                {durations.map((preset, index) => (
                                                    <span
                                                        key={preset.minutes}
                                                        className="flex items-center gap-1.5"
                                                    >
                                                        {index > 0 ? (
                                                            <span className="text-muted-foreground/50">
                                                                |
                                                            </span>
                                                        ) : null}
                                                        <button
                                                            type="button"
                                                            className="cursor-pointer text-muted-foreground transition-colors hover:text-primary"
                                                            onClick={() =>
                                                                setDurationMinutes(
                                                                    String(preset.minutes),
                                                                )
                                                            }
                                                        >
                                                            {preset.label}
                                                        </button>
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="flex h-9 items-center rounded-lg border border-input bg-card focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/50">
                                            <input
                                                id={pauseDurationId}
                                                className="h-full min-w-0 flex-1 bg-transparent px-2.5 outline-none"
                                                type="number"
                                                inputMode="numeric"
                                                min={1}
                                                max={525600}
                                                required
                                                value={durationMinutes}
                                                onChange={(event) =>
                                                    setDurationMinutes(event.target.value)
                                                }
                                            />
                                            <span className="pr-2.5 text-muted-foreground">m</span>
                                        </div>
                                    </div>
                                ) : null}
                            </div>

                            <DialogFooter>
                                <DialogClose
                                    render={
                                        <Button type="button" variant="ghost" disabled={working} />
                                    }
                                >
                                    Cancel
                                </DialogClose>
                                <Button type="submit" disabled={working || durationInvalid}>
                                    {working ? "Pausing…" : "Pause queue"}
                                </Button>
                            </DialogFooter>
                        </form>
                    ) : (
                        <>
                            <DialogHeader>
                                <DialogTitle>Pause {queue}</DialogTitle>
                                <DialogDescription>
                                    Are you sure you want to pause this queue?
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose
                                    render={
                                        <Button type="button" variant="ghost" disabled={working} />
                                    }
                                >
                                    Cancel
                                </DialogClose>
                                <Button
                                    type="button"
                                    disabled={working}
                                    onClick={() => void pause()}
                                >
                                    {working ? "Pausing…" : "Pause queue"}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
