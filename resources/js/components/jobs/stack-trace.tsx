import { CodeBlock } from "@/components/code-block";

export function StackTrace({ value }: { value: string }) {
    const frames = value.split("\n").filter((frame) => frame !== "");

    if (frames.length === 0) {
        return (
            <p className="px-6 py-4 text-sm text-muted-foreground">No exception was recorded.</p>
        );
    }

    return (
        <CodeBlock copyLabel="exception" copyValue={value}>
            <code>
                {frames.map((frame, index) => (
                    <span className="block" key={`${index}-${frame}`}>
                        <TraceLine value={frame} first={index === 0} />
                    </span>
                ))}
            </code>
        </CodeBlock>
    );
}

function TraceLine({ value, first }: { value: string; first: boolean }) {
    const exception = first ? value.match(/^([^:]+)(:)(.*)$/) : null;

    if (exception) {
        return (
            <>
                <span data-trace-token="exception" style={{ color: "var(--code-error)" }}>
                    {exception[1]}
                </span>
                <span style={{ color: "var(--code-punctuation)" }}>{exception[2]}</span>
                {exception[3]}
            </>
        );
    }

    if (value === "Stack trace:") {
        return <span style={{ color: "var(--code-muted)" }}>{value}</span>;
    }

    const locatedFrame = value.match(/^(#\d+)(\s+)(.+)\((\d+)\)(:\s*)(.*)$/);

    if (locatedFrame) {
        return (
            <>
                <span data-trace-token="frame" style={{ color: "var(--code-punctuation)" }}>
                    {locatedFrame[1]}
                </span>
                <span data-trace-token="path" style={{ color: "var(--code-muted)" }}>
                    {locatedFrame[2]}
                    {locatedFrame[3]}(
                </span>
                <span data-trace-token="line" style={{ color: "var(--code-number)" }}>
                    {locatedFrame[4]}
                </span>
                <span style={{ color: "var(--code-muted)" }}>{`)${locatedFrame[5]}`}</span>
                {locatedFrame[6]}
            </>
        );
    }

    const frame = value.match(/^(#\d+)(\s+)(.*)$/);

    if (frame) {
        return (
            <>
                <span data-trace-token="frame" style={{ color: "var(--code-punctuation)" }}>
                    {frame[1]}
                </span>
                {frame[2]}
                {frame[3]}
            </>
        );
    }

    return value;
}
