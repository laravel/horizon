import { index as horizonIndex } from "@/generated/routes/horizon";

type WayfinderRoute = {
    url: string;
};

const placeholderOrigin = "https://horizon.invalid";

/**
 * Split a URL-ish string into path / query / hash without using the URL
 * constructor on the path. `new URL()` normalizes bare `\` into `/`, which
 * corrupts FQCN tags and metric names before they can be percent-encoded.
 */
export function splitUrlParts(url: string): {
    pathname: string;
    search: string;
    hash: string;
} {
    const hashIndex = url.indexOf("#");
    const withoutHash = hashIndex === -1 ? url : url.slice(0, hashIndex);
    const hash = hashIndex === -1 ? "" : url.slice(hashIndex);

    const queryIndex = withoutHash.indexOf("?");
    const pathname = queryIndex === -1 ? withoutHash : withoutHash.slice(0, queryIndex);
    const search = queryIndex === -1 ? "" : withoutHash.slice(queryIndex);

    return { pathname, search, hash };
}

/**
 * Rebase a generated Horizon route path onto a runtime base URL while preserving
 * path characters (including encoded and raw backslashes) that URL.pathname would
 * otherwise normalize.
 */
export function rebaseHorizonRouteUrl(
    routeUrl: string,
    generatedBaseUrl: string,
    horizonBaseUrl: string,
): string {
    const generatedBasePath = splitUrlParts(generatedBaseUrl).pathname.replace(/\/+$/, "");
    const generatedRoute = splitUrlParts(routeUrl);

    if (
        generatedRoute.pathname !== generatedBasePath &&
        !generatedRoute.pathname.startsWith(`${generatedBasePath}/`)
    ) {
        throw new Error(`Cannot resolve a non-Horizon route: ${routeUrl}`);
    }

    const routeSuffix = generatedRoute.pathname.slice(generatedBasePath.length);

    if (URL.canParse(horizonBaseUrl)) {
        const configuredBase = new URL(horizonBaseUrl);
        const configuredBasePath = configuredBase.pathname.replace(/\/+$/, "");
        const pathname = `${configuredBasePath}${routeSuffix}` || "/";

        return `${configuredBase.origin}${pathname}${generatedRoute.search}${generatedRoute.hash}`;
    }

    const configuredBase = new URL(horizonBaseUrl, placeholderOrigin);
    const configuredBasePath = configuredBase.pathname.replace(/\/+$/, "");
    const pathname = `${configuredBasePath}${routeSuffix}` || "/";

    return `${pathname}${generatedRoute.search}${generatedRoute.hash}`;
}

/**
 * Rebase a Wayfinder-generated Horizon route onto the runtime Horizon base URL
 * (path and optional proxy prefix from shared Inertia props).
 */
export function resolveHorizonRoute<T extends WayfinderRoute>(route: T, horizonBaseUrl: string): T {
    return {
        ...route,
        url: rebaseHorizonRouteUrl(route.url, horizonIndex.url(), horizonBaseUrl),
    };
}
