import { index as horizonIndex } from "@/generated/routes/horizon";

type WayfinderRoute = {
    url: string;
};

const placeholderOrigin = "https://horizon.invalid";

/**
 * Rebase a Wayfinder-generated Horizon route onto the runtime Horizon base URL
 * (path and optional proxy prefix from shared Inertia props).
 */
export function resolveHorizonRoute<T extends WayfinderRoute>(route: T, horizonBaseUrl: string): T {
    const generatedBasePath = new URL(horizonIndex.url(), placeholderOrigin).pathname.replace(
        /\/+$/,
        "",
    );
    const generatedRoute = new URL(route.url, placeholderOrigin);

    if (
        generatedRoute.pathname !== generatedBasePath &&
        !generatedRoute.pathname.startsWith(`${generatedBasePath}/`)
    ) {
        throw new Error(`Cannot resolve a non-Horizon route: ${route.url}`);
    }

    const configuredBase = new URL(horizonBaseUrl, placeholderOrigin);
    const configuredBasePath = configuredBase.pathname.replace(/\/+$/, "");
    const routeSuffix = generatedRoute.pathname.slice(generatedBasePath.length);

    configuredBase.pathname = `${configuredBasePath}${routeSuffix}`;
    configuredBase.search = generatedRoute.search;
    configuredBase.hash = generatedRoute.hash;

    const url = URL.canParse(horizonBaseUrl)
        ? configuredBase.toString()
        : `${configuredBase.pathname}${configuredBase.search}${configuredBase.hash}`;

    return { ...route, url };
}
