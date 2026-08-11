import { spawnSync } from "node:child_process";
import { existsSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import path from "node:path";

const packagePath = process.cwd();
const testbenchPath = path.resolve(packagePath, "vendor/bin/testbench");
const generatedRoutesPath = path.resolve(packagePath, "resources/js/generated/routes");
const committedHorizonRoutesEntry = path.resolve(generatedRoutesPath, "horizon", "index.ts");

const hasCommittedHorizonRoutes = () => existsSync(committedHorizonRoutesEntry);

if (!existsSync(testbenchPath)) {
    if (hasCommittedHorizonRoutes()) {
        console.log(
            "[Wayfinder] vendor/bin/testbench is unavailable; using committed Horizon routes in resources/js/generated.",
        );
        process.exit(0);
    }

    console.error(
        "[Wayfinder] Cannot generate routes: vendor/bin/testbench is missing and no committed Horizon routes entry was found at resources/js/generated/routes/horizon/index.ts.",
    );
    process.exit(1);
}

const result = spawnSync("php", [testbenchPath, "wayfinder:generate", ...process.argv.slice(2)], {
    env: {
        ...process.env,
        APP_BASE_PATH: packagePath,
        VIEW_COMPILED_PATH: path.resolve(packagePath, "bootstrap/cache"),
    },
    stdio: "inherit",
});

if (result.error) {
    throw result.error;
}

if (result.status !== 0) {
    process.exitCode = result.status ?? 1;
} else {
    const retainedNamespaces = new Set(["horizon"]);

    for (const entry of readdirSync(generatedRoutesPath, { withFileTypes: true })) {
        if (entry.isDirectory() && !retainedNamespaces.has(entry.name)) {
            rmSync(path.join(generatedRoutesPath, entry.name), { force: true, recursive: true });
        }
    }

    const checkoutPrefixes = [
        packagePath.replaceAll("\\", "/").replace(/\/+$/, ""),
        packagePath.replaceAll("\\", "/").replace(/^\/+|\/+$/g, ""),
    ];

    const normalizeSourcePaths = (directory) => {
        for (const entry of readdirSync(directory, { withFileTypes: true })) {
            const entryPath = path.join(directory, entry.name);

            if (entry.isDirectory()) {
                normalizeSourcePaths(entryPath);
                continue;
            }

            if (!entry.name.endsWith(".ts")) {
                continue;
            }

            const content = readFileSync(entryPath, "utf8");
            const normalized = checkoutPrefixes.reduce(
                (source, prefix) => source.replaceAll(`${prefix}/`, ""),
                content,
            );

            if (normalized !== content) {
                writeFileSync(entryPath, normalized);
            }
        }
    };

    normalizeSourcePaths(generatedRoutesPath);
    encodePathParameters(generatedRoutesPath);
}

/**
 * Wayfinder substitutes path parameters with raw `.toString()` values. Horizon
 * tags and metric names are often FQCNs (`App\Jobs\Foo`) or slash-containing
 * queue/tag values; unencoded backslashes break after URL parsing, and slashes
 * must remain percent-encoded path segments. Wrap every path placeholder
 * replacement so regeneration always preserves encodeURIComponent behavior.
 */
function encodePathParameters(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            encodePathParameters(entryPath);
            continue;
        }

        if (!entry.name.endsWith(".ts")) {
            continue;
        }

        const content = readFileSync(entryPath, "utf8");
        const encoded = content.replace(
            /\.replace\(\s*(['"`]\{[^}'"`]+\}['"`])\s*,\s*((?:(?!encodeURIComponent)[^)])+)\s*\)/g,
            (match, placeholder, valueExpr) => {
                const trimmed = valueExpr.trim();

                if (trimmed.startsWith("encodeURIComponent(")) {
                    return match;
                }

                return `.replace(${placeholder}, encodeURIComponent(${trimmed}))`;
            },
        );

        if (encoded !== content) {
            writeFileSync(entryPath, encoded);
        }
    }
}
