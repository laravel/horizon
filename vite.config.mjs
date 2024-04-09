import vue2 from "@vitejs/plugin-vue2";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

const config = defineConfig({
    plugins: [
        laravel({
            input: {
                "app-css": "resources/sass/app.scss",
                "app-dark-css": "resources/sass/app-dark.scss",
                app: "resources/js/app.js",
            },
        }),
        vue2(),
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
