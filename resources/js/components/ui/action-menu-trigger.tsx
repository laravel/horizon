import { EllipsisIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import { DropdownMenuTrigger } from "@/components/ui/dropdown-menu";

export function ActionMenuTrigger({
    label,
    working = false,
}: {
    label: string;
    working?: boolean;
}) {
    return (
        <DropdownMenuTrigger
            render={
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    className="text-muted-foreground hover:text-primary focus-visible:text-primary aria-expanded:text-primary"
                    aria-label={label}
                    disabled={working}
                />
            }
        >
            <EllipsisIcon />
        </DropdownMenuTrigger>
    );
}
