export function isInteractiveTarget(target: EventTarget | null) {
    return (
        target instanceof Element &&
        target.closest("a, button, input, select, textarea, [role='button'], [role='link']") !==
            null
    );
}
