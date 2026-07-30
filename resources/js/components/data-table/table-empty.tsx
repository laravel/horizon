import { InboxIcon, type LucideIcon } from "lucide-react";

import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import { TableCell, TableRow } from "@/components/ui/table";

export function TableEmpty({
    columns,
    title = "Nothing here yet",
    description = "Entries will appear here when Horizon reports them.",
    icon: Icon = InboxIcon,
}: {
    columns: number;
    title?: string;
    description?: string;
    icon?: LucideIcon;
}) {
    return (
        <TableRow aria-label={title} className="hover:bg-transparent">
            <TableCell className="p-0 whitespace-normal" colSpan={columns}>
                <Empty className="min-h-44">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Icon aria-hidden="true" />
                        </EmptyMedia>
                        <EmptyTitle>{title}</EmptyTitle>
                        <EmptyDescription>{description}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </TableCell>
        </TableRow>
    );
}
