import { registerBlatUI } from "./blatui-core.js";

document.addEventListener("alpine:init", () => {
    registerBlatUI(window.Alpine, { darkMode: "system" });
});

document.addEventListener("livewire:navigated", () => {
    window.Alpine?.store("theme")?.apply();
});
