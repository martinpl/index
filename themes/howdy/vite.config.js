import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/theme.css", "resources/css/blocks.css"],
            refresh: ["resources/views/**", "src/**"],
        }),
    ],
    server: {
        cors: true,
    },
});
