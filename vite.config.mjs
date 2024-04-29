import vue2 from "@vitejs/plugin-vue2";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { createHash } from "node:crypto";
import { resolve } from "node:path";
import { readFileSync, writeFileSync } from "node:fs";

function manifestQueryParam() {
    return {
        name: "vite-horizon-manifest-query-param",
        apply: "build",
        enforce: "post",
        writeBundle({ dir }) {
            const manifestPath = resolve(dir, "manifest.json");
            const manifest = readFileSync(manifestPath, "utf-8");

            if (manifest) {
                const parsedManifest = JSON.parse(manifest);

                for (const property in parsedManifest) {
                    const fileContent = readFileSync(
                        resolve(dir, parsedManifest[property].file),
                        "utf-8"
                    );

                    parsedManifest[property].file += `?id=${createHash("md5")
                        .update(fileContent)
                        .digest("hex")}`;
                }

                writeFileSync(
                    manifestPath,
                    JSON.stringify(parsedManifest, null, 2)
                );
            }
        },
    };
}

const config = defineConfig({
    plugins: [
        laravel([
            "resources/sass/styles.scss",
            "resources/sass/styles-dark.scss",
            "resources/js/app.js",
        ]),
        vue2(),
        manifestQueryParam(),
    ],
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm.js",
        },
    },
    build: {
        rollupOptions: {
            output: {
                entryFileNames: `[name].js`,
                chunkFileNames: `[name].js`,
                assetFileNames: `[name].[ext]`,
            },
        },
    },
});

export default config;
