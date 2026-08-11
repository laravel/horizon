import { Fragment, useRef } from "react";
import { ChevronRightIcon } from "lucide-react";

import { CodeBlock } from "@/components/code-block";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";

const jsonToken =
    /"(?:\\u[a-fA-F0-9]{4}|\\[^u]|[^\\"])*"|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|true|false|null|[{}[\],:]/g;

type JsonNodeProps = {
    value: unknown;
    depth: number;
    propertyName?: string;
    itemIndex?: number;
    trailingComma?: boolean;
    preserveTogglePosition: (trigger: HTMLButtonElement) => void;
};

function highlightedJson(contents: string) {
    const nodes: React.ReactNode[] = [];
    let cursor = 0;

    for (const match of contents.matchAll(jsonToken)) {
        const index = match.index;

        if (index > cursor) {
            nodes.push(contents.slice(cursor, index));
        }

        const token = match[0];
        const remainder = contents.slice(index + token.length);
        const color = token.startsWith('"')
            ? remainder.trimStart().startsWith(":")
                ? "var(--code-key)"
                : "var(--code-string)"
            : /^[{}[\],:]$/.test(token)
              ? "var(--code-punctuation)"
              : "var(--code-number)";

        nodes.push(
            <span style={{ color }} key={`${index}-${token}`}>
                {token}
            </span>,
        );
        cursor = index + token.length;
    }

    if (cursor < contents.length) {
        nodes.push(contents.slice(cursor));
    }

    return nodes.map((node, index) => <Fragment key={index}>{node}</Fragment>);
}

function JsonPunctuation({ children }: { children: React.ReactNode }) {
    return <span style={{ color: "var(--code-punctuation)" }}>{children}</span>;
}

function JsonPropertyName({ value }: { value?: string }) {
    if (value === undefined) {
        return null;
    }

    return (
        <>
            <span style={{ color: "var(--code-key)" }}>{JSON.stringify(value)}</span>
            <JsonPunctuation>: </JsonPunctuation>
        </>
    );
}

function JsonScalar({ value }: { value: unknown }) {
    const contents = JSON.stringify(value) ?? String(value);
    const color = typeof value === "string" ? "var(--code-string)" : "var(--code-number)";

    return <span style={{ color }}>{contents}</span>;
}

function JsonNode({
    value,
    depth,
    propertyName,
    itemIndex,
    trailingComma = false,
    preserveTogglePosition,
}: JsonNodeProps) {
    const triggerRef = useRef<HTMLButtonElement>(null);
    const indentation = "    ".repeat(depth);
    const isContainer = value !== null && typeof value === "object";

    if (!isContainer) {
        return (
            <span className="block">
                {indentation}
                <JsonPropertyName value={propertyName} />
                <JsonScalar value={value} />
                {trailingComma ? <JsonPunctuation>,</JsonPunctuation> : null}
            </span>
        );
    }

    const isArray = Array.isArray(value);
    const entries = isArray ? value : Object.entries(value);
    const openingDelimiter = isArray ? "[" : "{";
    const closingDelimiter = isArray ? "]" : "}";

    if (entries.length === 0) {
        return (
            <span className="block">
                {indentation}
                <JsonPropertyName value={propertyName} />
                <JsonPunctuation>
                    {openingDelimiter}
                    {closingDelimiter}
                    {trailingComma ? "," : null}
                </JsonPunctuation>
            </span>
        );
    }

    const containerName = isArray ? "array" : "object";
    const entryName = isArray
        ? entries.length === 1
            ? "item"
            : "items"
        : entries.length === 1
          ? "property"
          : "properties";
    const accessibleName =
        propertyName ?? (itemIndex === undefined ? "Root" : `Item ${itemIndex + 1}`);

    return (
        <Collapsible
            defaultOpen
            onOpenChange={() => {
                if (triggerRef.current !== null) {
                    preserveTogglePosition(triggerRef.current);
                }
            }}
            render={<span className="block" />}
        >
            <span className="block">
                {indentation}
                <CollapsibleTrigger
                    ref={triggerRef}
                    type="button"
                    aria-label={`${accessibleName} ${containerName}, ${entries.length} ${entryName}`}
                    className="group relative -ml-4 inline-flex max-w-[calc(100%+1rem)] cursor-pointer items-center rounded-sm pr-1 pl-4 text-left text-inherit outline-none hover:bg-code-control-hover focus-visible:bg-code-control-hover focus-visible:ring-2 focus-visible:ring-code-control-foreground/60"
                >
                    <ChevronRightIcon
                        aria-hidden="true"
                        className="absolute left-0 size-3.5 text-code-control-foreground transition-[transform,color] duration-150 group-data-panel-open:rotate-90 group-hover:text-code-control-hover-foreground motion-reduce:transition-none"
                    />
                    <JsonPropertyName value={propertyName} />
                    <JsonPunctuation>{openingDelimiter}</JsonPunctuation>
                    <span className="group-data-panel-open:hidden">
                        <JsonPunctuation>
                            …{closingDelimiter}
                            {trailingComma ? "," : null}
                        </JsonPunctuation>
                        <span className="ml-2 text-[11px] font-normal text-code-control-foreground">
                            {entries.length} {entryName}
                        </span>
                    </span>
                </CollapsibleTrigger>
            </span>

            <CollapsibleContent keepMounted render={<span className="block" />}>
                {isArray
                    ? entries.map((entry, index) => (
                          <JsonNode
                              value={entry}
                              depth={depth + 1}
                              itemIndex={index}
                              trailingComma={index < entries.length - 1}
                              preserveTogglePosition={preserveTogglePosition}
                              key={index}
                          />
                      ))
                    : entries.map(([name, entry], index) => (
                          <JsonNode
                              value={entry}
                              depth={depth + 1}
                              propertyName={name}
                              trailingComma={index < entries.length - 1}
                              preserveTogglePosition={preserveTogglePosition}
                              key={name}
                          />
                      ))}
                <span className="block">
                    {indentation}
                    <JsonPunctuation>
                        {closingDelimiter}
                        {trailingComma ? "," : null}
                    </JsonPunctuation>
                </span>
            </CollapsibleContent>
        </Collapsible>
    );
}

function serializeJson(value: unknown): string | null {
    if (typeof value === "string") {
        return value;
    }

    try {
        return JSON.stringify(value, null, 4) ?? null;
    } catch {
        return null;
    }
}

export function JsonPayload({
    value,
    copyLabel = "job data",
}: {
    value: unknown;
    copyLabel?: string;
}) {
    const scrollSpacerRef = useRef<HTMLSpanElement>(null);
    const pendingFrameRef = useRef<number | null>(null);
    const pendingTriggerRef = useRef<HTMLButtonElement | null>(null);
    const pendingScrollLeftRef = useRef(0);
    const pendingScrollTopRef = useRef(0);

    const preserveTogglePosition = (trigger: HTMLButtonElement) => {
        pendingTriggerRef.current = trigger;
        pendingScrollLeftRef.current = window.scrollX;
        pendingScrollTopRef.current = window.scrollY;

        if (pendingFrameRef.current !== null) {
            cancelAnimationFrame(pendingFrameRef.current);
        }

        pendingFrameRef.current = requestAnimationFrame(() => {
            pendingFrameRef.current = null;

            const pendingTrigger = pendingTriggerRef.current;
            const scrollSpacer = scrollSpacerRef.current;
            const scrollElement = document.scrollingElement;

            if (
                pendingTrigger === null ||
                scrollSpacer === null ||
                scrollElement === null ||
                !pendingTrigger.isConnected
            ) {
                return;
            }

            const scrollLeft = pendingScrollLeftRef.current;
            const scrollTop = pendingScrollTopRef.current;
            const documentHeightWithoutSpacer =
                scrollElement.scrollHeight - scrollSpacer.offsetHeight;
            const requiredSpacerHeight = Math.ceil(
                Math.max(0, scrollTop + scrollElement.clientHeight - documentHeightWithoutSpacer),
            );

            scrollSpacer.style.height = `${requiredSpacerHeight}px`;
            window.scrollTo(scrollLeft, scrollTop);
        });
    };

    if (value === null || value === undefined) {
        return <p className="px-6 py-4 text-sm text-muted-foreground">Payload unavailable</p>;
    }

    const contents = serializeJson(value);

    if (contents === null) {
        return <p className="px-6 py-4 text-sm text-muted-foreground">Payload unavailable</p>;
    }

    const normalizedValue = typeof value === "string" ? value : JSON.parse(contents);

    return (
        <CodeBlock
            className="whitespace-pre-wrap break-words [overflow-anchor:none]"
            copyLabel={copyLabel}
            copyValue={contents}
        >
            <code>
                {typeof value === "string" ? (
                    highlightedJson(contents)
                ) : (
                    <>
                        <JsonNode
                            value={normalizedValue}
                            depth={0}
                            preserveTogglePosition={preserveTogglePosition}
                        />
                        <span ref={scrollSpacerRef} aria-hidden="true" className="block h-0" />
                    </>
                )}
            </code>
        </CodeBlock>
    );
}
