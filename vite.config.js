import tailwindcss from "@tailwindcss/vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import { defineConfig, lazyPlugins } from "vite-plus";

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/passkeys.js",
                "resources/js/head.js",
            ],
            refresh: true,
            fonts: [
                bunny("Instrument Sans", {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    css: {
        postcss: {
            plugins: [scopeDashboardStyles("resources/css/app.css")],
        },
    },
    server: {
        cors: true,
        watch: {
            ignored: [
                "**/.agents/**",
                "**/.claude/**",
                "**/.cursor/**",
                "**/.junie/**",
                "**/storage/framework/views/**",
                "**/vendor/**",
            ],
        },
    },
});

/**
 * Keep the dashboard styles off the site's markup (`data-canvas`), except the admin bar
 * (`data-admin-bar`), by wrapping the rules in `@scope` and moving the document rules
 * (`:root`, `html`, `body`, `.dark`) to the dashboard's `<html>` or the admin bar.
 *
 * @param {string} file
 */
function scopeDashboardStyles(file) {
    const scope =
        "(:root:not([data-canvas]), [data-canvas] > body) to (:scope:is(body) > :not([data-admin-bar]), [data-canvas])";
    const documentState = /^(?:\.dark|\[[^\]]*\])+(?=$|[\s>+~])/;

    const scopeSelector = (selector) => {
        if (/^(:root|:host|html)$/.test(selector)) {
            return [":scope:not(body)", "body:scope > *"];
        }

        if (selector === "body") {
            return ["body", "body:scope > *"];
        }

        if (selector === "*") {
            return ["*", ":scope:not(body)"];
        }

        const [state] = selector.match(documentState) ?? [];

        if (!state) {
            return [selector];
        }

        const rest = selector.slice(state.length);
        const selectors = [selector, `:scope:not(body)${state}${rest}`];

        if (!state.includes("[data-")) {
            selectors.push(`${state} body:scope${rest || " > *"}`);
        }

        return selectors;
    };

    const wrapRules = (container, AtRule) => {
        let group = null;

        for (const node of [...container.nodes]) {
            if (isScopable(node) || (group && node.type === "comment")) {
                if (!group) {
                    group = new AtRule({ name: "scope", params: scope });
                    node.before(group);
                }

                group.append(node);
            } else {
                group = null;

                if (node.type === "atrule" && node.name === "layer" && node.nodes) {
                    wrapRules(node, AtRule);
                }
            }
        }
    };

    return {
        postcssPlugin: "scope-dashboard-styles",
        Once(root, { result, AtRule }) {
            if (!result.opts.from?.includes(file)) {
                return;
            }

            wrapRules(root, AtRule);

            root.walkRules((rule) => {
                if (isNestedOrKeyframe(rule)) {
                    return;
                }

                rule.selectors = [
                    ...new Set(rule.selectors.flatMap(scopeSelector)),
                ];
            });
        },
    };
}

/**
 * Whether the node holds style rules a `@scope` block may wrap.
 */
function isScopable(node) {
    return (
        node.type === "rule" ||
        (node.type === "atrule" &&
            ["media", "supports", "container"].includes(node.name))
    );
}

/**
 * Whether the rule is a keyframe, or sits in a rule already scoped as its parent.
 */
function isNestedOrKeyframe(rule) {
    for (let node = rule.parent; node; node = node.parent) {
        if (node.type === "rule") {
            return true;
        }

        if (node.type === "atrule" && node.name.endsWith("keyframes")) {
            return true;
        }
    }

    return false;
}
