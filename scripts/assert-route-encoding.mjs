import { readdirSync, readFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const packagePath = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const generatedRoutesPath = path.resolve(packagePath, "resources/js/generated/routes");
const generatorPath = path.resolve(packagePath, "scripts/generate-wayfinder.mjs");
const horizonRoutePath = path.resolve(packagePath, "resources/js/lib/horizon-route.ts");

const failures = [];

const walkTsFiles = (directory) => {
    const files = [];

    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            files.push(...walkTsFiles(entryPath));
            continue;
        }

        if (entry.name.endsWith(".ts")) {
            files.push(entryPath);
        }
    }

    return files;
};

// Mirror of resources/js/lib/horizon-route.ts — keep in sync when changing rebasing.
const splitUrlParts = (url) => {
    const hashIndex = url.indexOf("#");
    const withoutHash = hashIndex === -1 ? url : url.slice(0, hashIndex);
    const hash = hashIndex === -1 ? "" : url.slice(hashIndex);
    const queryIndex = withoutHash.indexOf("?");
    const pathname = queryIndex === -1 ? withoutHash : withoutHash.slice(0, queryIndex);
    const search = queryIndex === -1 ? "" : withoutHash.slice(queryIndex);

    return { pathname, search, hash };
};

const rebaseHorizonRouteUrl = (routeUrl, generatedBaseUrl, horizonBaseUrl) => {
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

    const configuredBase = new URL(horizonBaseUrl, "https://horizon.invalid");
    const configuredBasePath = configuredBase.pathname.replace(/\/+$/, "");
    const pathname = `${configuredBasePath}${routeSuffix}` || "/";

    return `${pathname}${generatedRoute.search}${generatedRoute.hash}`;
};

// 1) Generated path substitutions must encode parameter values.
const unencodedPathReplaces = [];

for (const file of walkTsFiles(generatedRoutesPath)) {
    const content = readFileSync(file, "utf8");
    const re = /\.replace\(\s*(['"`]\{[^}'"`]+\}['"`])\s*,\s*([^)]+)\)/g;
    let match;

    while ((match = re.exec(content)) !== null) {
        const [, placeholder, valueExpr] = match;

        if (!valueExpr.includes("encodeURIComponent")) {
            unencodedPathReplaces.push(
                `${path.relative(packagePath, file)}: .replace(${placeholder}, ${valueExpr.trim()})`,
            );
        }
    }
}

if (unencodedPathReplaces.length > 0) {
    failures.push(
        "Generated route helpers substitute unencoded path parameters:\n" +
            unencodedPathReplaces.map((line) => `  - ${line}`).join("\n"),
    );
}

// 2) Generator must re-apply encoding so regeneration is reproducible.
const generatorSource = readFileSync(generatorPath, "utf8");

if (
    !generatorSource.includes("encodePathParameters") ||
    !generatorSource.includes("encodeURIComponent")
) {
    failures.push(
        "scripts/generate-wayfinder.mjs must post-process path parameters with encodeURIComponent.",
    );
}

// 3) horizon-route.ts must not re-parse the route path through new URL().
const horizonRouteSource = readFileSync(horizonRoutePath, "utf8");

if (
    !horizonRouteSource.includes("splitUrlParts") ||
    !horizonRouteSource.includes("rebaseHorizonRouteUrl") ||
    /new URL\(\s*route\.url/.test(horizonRouteSource) ||
    /new URL\(\s*routeUrl/.test(horizonRouteSource)
) {
    failures.push(
        "resources/js/lib/horizon-route.ts must rebase via splitUrlParts/rebaseHorizonRouteUrl without new URL(route.url).",
    );
}

// 4) Runtime encoding + rebasing behavior for FQCNs and slash-containing values.
const encodePath = (definition, params) => {
    let url = definition;

    for (const [key, value] of Object.entries(params)) {
        url = url.replace(`{${key}}`, encodeURIComponent(String(value)));
    }

    return url.replace(/\/+$/, "");
};

const fqcn = "App\\Jobs\\SendInvoice";
const slashTag = "customer/vip";
const slashQueue = "reports/daily";
const jobWithSlash = "App\\Jobs\\Import/Orders";

const monitoringUrl = encodePath("/horizon/monitoring/{tag}/jobs", { tag: fqcn });
const slashTagUrl = encodePath("/horizon/monitoring/{tag}/jobs", { tag: slashTag });
const pauseUrl = encodePath("/horizon/api/queues/{connection}/{queue}/pause", {
    connection: "redis",
    queue: slashQueue,
});
const metricUrl = encodePath("/horizon/metrics/{type}/{name}", {
    type: "jobs",
    name: jobWithSlash,
});

const expect = (label, actual, expected) => {
    if (actual !== expected) {
        failures.push(`${label}\n  expected: ${expected}\n  actual:   ${actual}`);
    }
};

expect("FQCN monitoring tag", monitoringUrl, "/horizon/monitoring/App%5CJobs%5CSendInvoice/jobs");
expect("slash-containing monitoring tag", slashTagUrl, "/horizon/monitoring/customer%2Fvip/jobs");
expect(
    "slash-containing queue pause path",
    pauseUrl,
    "/horizon/api/queues/redis/reports%2Fdaily/pause",
);
expect(
    "FQCN metric name with slash",
    metricUrl,
    "/horizon/metrics/jobs/App%5CJobs%5CImport%2FOrders",
);

expect(
    "rebase encoded FQCN onto proxy path",
    rebaseHorizonRouteUrl(monitoringUrl, "/horizon", "/ops/horizon"),
    "/ops/horizon/monitoring/App%5CJobs%5CSendInvoice/jobs",
);

// Defense: raw backslash must not become a slash when rebasing.
const rawBackslashUrl = `/horizon/monitoring/${fqcn}/jobs`;
const rebasedRaw = rebaseHorizonRouteUrl(rawBackslashUrl, "/horizon", "/horizon");
const brokenViaUrlConstructor = new URL(rawBackslashUrl, "https://horizon.invalid").pathname;

if (!brokenViaUrlConstructor.includes("/App/Jobs/")) {
    failures.push(
        "sanity: expected new URL() to normalize backslashes; environment differs from assumed bug",
    );
}

if (rebasedRaw.includes("/App/Jobs/") || !rebasedRaw.includes("\\")) {
    failures.push(`rebase raw FQCN must preserve backslash segments\n  actual: ${rebasedRaw}`);
}

expect(
    "rebase slash-containing queue pause",
    rebaseHorizonRouteUrl(pauseUrl, "/horizon", "/horizon"),
    "/horizon/api/queues/redis/reports%2Fdaily/pause",
);

expect(
    "rebase slash-containing monitoring tag",
    rebaseHorizonRouteUrl(slashTagUrl, "/horizon", "/gateway/horizon"),
    "/gateway/horizon/monitoring/customer%2Fvip/jobs",
);

if (failures.length > 0) {
    console.error("Route encoding assertions failed:\n\n" + failures.join("\n\n"));
    process.exit(1);
}

console.log("Route encoding assertions passed.");
