export async function copyToClipboard(value: string): Promise<boolean> {
    if (navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(value);

            return true;
        } catch {
            // Browsers can expose the Clipboard API while denying the write.
        }
    }

    const input = document.createElement("textarea");
    input.value = value;
    input.setAttribute("readonly", "");
    input.style.position = "fixed";
    input.style.opacity = "0";
    const previouslyFocusedElement =
        document.activeElement instanceof HTMLElement ? document.activeElement : null;

    try {
        document.body.append(input);
        input.select();

        return document.execCommand("copy");
    } catch {
        return false;
    } finally {
        input.remove();

        if (previouslyFocusedElement?.isConnected) {
            previouslyFocusedElement.focus({ preventScroll: true });
        }
    }
}
