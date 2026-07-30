import { Head, Link, router, useHttp, usePage } from "@inertiajs/react";
import { PlusIcon, Trash2Icon } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { TableEmpty } from "@/components/data-table/table-empty";
import { MonitoringNavigationIcon } from "@/components/navigation-icons";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { store as monitorTagStore } from "@/generated/routes/horizon/monitoring";
import { page as monitoringJobsPage } from "@/generated/routes/horizon/monitoring-jobs";
import { destroy as destroyMonitoringTag } from "@/generated/routes/horizon/monitoring-tag";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { HorizonPageProps } from "@/types/dashboard";

type MonitoredTag = {
    tag: string;
    count: number;
};

export default function Monitoring({ tags }: { tags: MonitoredTag[] }) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const [dialogOpen, setDialogOpen] = useState(false);
    const [newTag, setNewTag] = useState("");
    const monitorRequest = useHttp<{ tag: string }>({ tag: "" });
    const stopRequest = useHttp({});
    const storeUrl = resolveHorizonRoute(monitorTagStore(), horizon.baseUrl).url;

    async function monitorTag() {
        const tag = newTag.trim();

        if (tag === "") {
            return;
        }

        monitorRequest.setData("tag", tag);

        try {
            await monitorRequest.post(storeUrl, {
                onSuccess: () => {
                    setDialogOpen(false);
                    setNewTag("");
                    toast.success(`Monitoring ${tag}.`);
                    router.reload({ only: ["tags", "navigationCounts"] });
                },
                onError: () => {
                    toast.error("The tag could not be monitored.");
                },
                onHttpException: () => {
                    toast.error("The tag could not be monitored.");
                },
                onNetworkError: () => {
                    toast.error("The tag could not be monitored.");
                },
            });
        } catch {
            // The request callbacks already present the actionable failure.
        }
    }

    async function stopMonitoring(tag: string) {
        const destroyUrl = resolveHorizonRoute(destroyMonitoringTag(tag), horizon.baseUrl).url;

        try {
            await stopRequest.delete(destroyUrl, {
                onSuccess: () => {
                    toast.success(`Stopped monitoring ${tag}.`);
                    router.reload({ only: ["tags", "navigationCounts"] });
                },
                onError: () => {
                    toast.error(`${tag} could not be unmonitored.`);
                },
                onHttpException: () => {
                    toast.error(`${tag} could not be unmonitored.`);
                },
                onNetworkError: () => {
                    toast.error(`${tag} could not be unmonitored.`);
                },
            });
        } catch {
            // The request callbacks already present the actionable failure.
        }
    }

    const working = monitorRequest.processing || stopRequest.processing;

    return (
        <>
            <Head title="Horizon - Monitoring" />
            <Card>
                <CardHeader>
                    <CardTitle>Monitoring</CardTitle>
                    <Button type="button" onClick={() => setDialogOpen(true)} disabled={working}>
                        <PlusIcon />
                        Monitor Tag
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tag</TableHead>
                                <TableHead className="text-right">Jobs</TableHead>
                                <TableHead className="w-px text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tags.length === 0 ? (
                                <TableEmpty
                                    columns={3}
                                    description="Monitor a tag to retain matching completed jobs."
                                    icon={MonitoringNavigationIcon}
                                    title="No monitored tags"
                                />
                            ) : null}
                            {tags.map((entry) => {
                                const detailUrl = resolveHorizonRoute(
                                    monitoringJobsPage(entry.tag),
                                    horizon.baseUrl,
                                ).url;

                                return (
                                    <TableRow
                                        className="cursor-pointer"
                                        key={entry.tag}
                                        onClick={(event) => {
                                            if (!isInteractiveTarget(event.target)) {
                                                router.visit(detailUrl);
                                            }
                                        }}
                                        onMouseEnter={() => router.prefetch(detailUrl)}
                                    >
                                        <TableCell>
                                            <Link
                                                href={detailUrl}
                                                className="font-medium hover:text-primary"
                                                prefetch
                                            >
                                                {entry.tag}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums text-muted-foreground">
                                            {entry.count.toLocaleString()}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                variant="action"
                                                size="icon-sm"
                                                className="text-muted-foreground focus-visible:text-primary"
                                                aria-label={`Stop monitoring ${entry.tag}`}
                                                disabled={working}
                                                onClick={() => void stopMonitoring(entry.tag)}
                                            >
                                                <Trash2Icon />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            void monitorTag();
                        }}
                    >
                        <DialogHeader>
                            <DialogTitle>Monitor Tag</DialogTitle>
                            <DialogDescription>
                                Horizon will retain completed jobs that include this exact tag.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-5">
                            <label className="flex flex-col gap-2">
                                <span className="font-medium">Tag</span>
                                <input
                                    autoFocus
                                    className="h-9 rounded-lg border border-input bg-card px-2.5 outline-none focus:border-ring focus:ring-3 focus:ring-ring/50"
                                    value={newTag}
                                    onChange={(event) => setNewTag(event.target.value)}
                                    placeholder="App\Jobs\ExampleJob"
                                />
                            </label>
                        </div>
                        <DialogFooter>
                            <DialogClose
                                render={<Button type="button" variant="ghost" disabled={working} />}
                            >
                                Cancel
                            </DialogClose>
                            <Button type="submit" disabled={working || newTag.trim() === ""}>
                                {monitorRequest.processing ? "Monitoring…" : "Monitor Tag"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
