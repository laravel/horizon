import { wayfinder } from "@laravel/vite-plugin-wayfinder";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite-plus";

export default defineConfig({
    lint: {
        ignorePatterns: ["resources/js/generated/**"],
        options: {
            typeAware: true,
            typeCheck: true,
        },
    },
    fmt: {
        ignorePatterns: ["resources/js/generated/**"],
    },
    // Relative asset URLs so fingerprinted fonts resolve next to CSS under
    // public/vendor/horizon/build (and proxy_path prefixes) after publish.
    base: "./",
    plugins: [
        laravel({
            input: ["resources/js/app.tsx"],
            publicDirectory: "dist",
            buildDirectory: "build",
            hotFile: "bootstrap/cache/horizon-vite.hot",
            assets: "resources/images/favicon.svg",
        }),
        wayfinder({
            actions: false,
            command: "node scripts/generate-wayfinder.mjs",
            path: "resources/js/generated",
            patterns: ["routes/**/*.php", "src/Http/**/*.php"],
        }),
        react(),
        tailwindcss(),
    ],
});
