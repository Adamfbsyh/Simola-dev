
const STORAGE_KEY = 'simola-theme';
const root = document.documentElement;

const systemTheme = () =>
    window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';

const chartCollection = () => {
    if (!window.Chart || !window.Chart.instances) {
        return [];
    }

    const instances = window.Chart.instances;

    if (instances instanceof Map) {
        return Array.from(instances.values());
    }

    return Object.values(instances);
};

const syncCharts = (theme) => {
    if (!window.Chart) {
        return;
    }

    const dark = theme === 'dark';
    const text = dark ? '#cbd5e1' : '#475569';
    const muted = dark ? '#94a3b8' : '#64748b';
    const grid = dark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(148, 163, 184, 0.26)';
    const tooltipBg = dark ? '#0f172a' : '#ffffff';
    const tooltipText = dark ? '#f8fafc' : '#0f172a';

    try {
        window.Chart.defaults.color = text;
        window.Chart.defaults.borderColor = grid;
    } catch (error) {
        // Older/custom Chart.js build. Instance updates below are still attempted.
    }

    chartCollection().forEach((chart) => {
        if (!chart || !chart.options) {
            return;
        }

        if (chart.options.plugins?.legend?.labels) {
            chart.options.plugins.legend.labels.color = text;
        }

        if (chart.options.plugins?.title) {
            chart.options.plugins.title.color = text;
        }

        if (chart.options.plugins?.tooltip) {
            chart.options.plugins.tooltip.backgroundColor = tooltipBg;
            chart.options.plugins.tooltip.titleColor = tooltipText;
            chart.options.plugins.tooltip.bodyColor = tooltipText;
            chart.options.plugins.tooltip.borderColor = dark ? '#334155' : '#e2e8f0';
            chart.options.plugins.tooltip.borderWidth = 1;
        }

        const scales = chart.options.scales || {};

        Object.values(scales).forEach((scale) => {
            if (!scale) {
                return;
            }

            scale.ticks = scale.ticks || {};
            scale.ticks.color = muted;

            scale.grid = scale.grid || {};
            scale.grid.color = grid;

            scale.border = scale.border || {};
            scale.border.color = grid;

            if (scale.title) {
                scale.title.color = text;
            }
        });

        try {
            chart.update('none');
        } catch (error) {
            try {
                chart.update();
            } catch (ignored) {
                // Do not break the page if a custom chart wrapper rejects updates.
            }
        }
    });
};

const refreshLabels = (theme) => {
    document
        .querySelectorAll('[data-theme-label]')
        .forEach((node) => {
            // Label describes the action, not the current state.
            node.textContent = theme === 'dark'
                ? 'Light mode'
                : 'Dark mode';
        });
};

const applyTheme = (requestedTheme, persist = false) => {
    const theme = requestedTheme === 'dark'
        ? 'dark'
        : 'light';

    root.classList.toggle('dark', theme === 'dark');
    root.dataset.theme = theme;

    if (persist) {
        localStorage.setItem(STORAGE_KEY, theme);
    }

    refreshLabels(theme);

    window.dispatchEvent(
        new CustomEvent('simola-theme-changed', {
            detail: { theme },
        })
    );

    window.setTimeout(() => syncCharts(theme), 0);
    window.setTimeout(() => syncCharts(theme), 250);
    window.setTimeout(() => syncCharts(theme), 900);
};

const storedTheme = localStorage.getItem(STORAGE_KEY);
applyTheme(storedTheme || systemTheme());

window.addEventListener('DOMContentLoaded', () => {
    refreshLabels(
        root.classList.contains('dark')
            ? 'dark'
            : 'light'
    );

    document
        .querySelectorAll('[data-theme-toggle]')
        .forEach((button) => {
            if (button.dataset.themeBound === '1') {
                return;
            }

            button.dataset.themeBound = '1';

            button.addEventListener('click', () => {
                const nextTheme =
                    root.classList.contains('dark')
                        ? 'light'
                        : 'dark';

                applyTheme(nextTheme, true);
            });
        });

    window.setTimeout(
        () => syncCharts(
            root.classList.contains('dark')
                ? 'dark'
                : 'light'
        ),
        500
    );
});

const media = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemTheme = (event) => {
    if (localStorage.getItem(STORAGE_KEY)) {
        return;
    }

    applyTheme(
        event.matches
            ? 'dark'
            : 'light'
    );
};

if (typeof media.addEventListener === 'function') {
    media.addEventListener('change', handleSystemTheme);
} else if (typeof media.addListener === 'function') {
    media.addListener(handleSystemTheme);
}

/* === SIMOLA UNIFIED THEME JS v2.0 START === */
/* ==========================================================================
   SIMOLA UNIFIED THEME ENGINE v2.0
   Appearance = Light/Dark. Dashboard preset = accent only.
   ========================================================================== */

(() => {
    if (window.__simolaUnifiedThemeV20) {
        return;
    }

    window.__simolaUnifiedThemeV20 = true;

    const root = document.documentElement;

    const normalize = (value) =>
        String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

    const exactNodes = (text, scope = document) =>
        Array.from(
            scope.querySelectorAll(
                'h1,h2,h3,h4,h5,p,span,div,strong,b,small,label,button,a'
            )
        ).filter((node) => {
            if (!(node instanceof HTMLElement)) {
                return false;
            }

            if (node.children.length > 3) {
                return false;
            }

            return normalize(node.textContent) === normalize(text);
        });

    const rectVisible = (element) => {
        const rect = element.getBoundingClientRect();

        return (
            rect.width > 0 &&
            rect.height > 0 &&
            window.getComputedStyle(element).display !== 'none'
        );
    };

    const findCardAncestor = (node) => {
        const candidates = [];
        let current = node;

        for (let depth = 0; current && depth < 8; depth += 1) {
            if (!(current instanceof HTMLElement)) {
                break;
            }

            const rect = current.getBoundingClientRect();
            const style = window.getComputedStyle(current);

            if (
                rect.width >= 135 &&
                rect.width <= 360 &&
                rect.height >= 60 &&
                rect.height <= 175 &&
                rectVisible(current) &&
                !current.closest('nav')
            ) {
                let score = 0;

                if (parseFloat(style.borderRadius || '0') >= 8) {
                    score += 4;
                }

                if (parseFloat(style.borderWidth || '0') > 0) {
                    score += 3;
                }

                if (rect.width >= 160 && rect.width <= 260) {
                    score += 4;
                }

                if (rect.height >= 70 && rect.height <= 130) {
                    score += 3;
                }

                score += Math.min(
                    4,
                    current.children.length
                );

                candidates.push({
                    element: current,
                    score,
                    area: rect.width * rect.height,
                });
            }

            current = current.parentElement;
        }

        candidates.sort((a, b) => {
            if (b.score !== a.score) {
                return b.score - a.score;
            }

            return b.area - a.area;
        });

        return candidates[0]?.element || null;
    };

    const findWideSection = (heading, type) => {
        for (const node of exactNodes(heading)) {
            let current = node.parentElement;

            for (let depth = 0; current && depth < 8; depth += 1) {
                if (!(current instanceof HTMLElement)) {
                    break;
                }

                const rect = current.getBoundingClientRect();
                const style = window.getComputedStyle(current);

                if (
                    rect.width >= 650 &&
                    rect.height >= 45 &&
                    rect.height <= 520 &&
                    rectVisible(current) &&
                    !current.closest('nav') &&
                    (
                        parseFloat(style.borderRadius || '0') >= 8 ||
                        parseFloat(style.borderWidth || '0') > 0
                    )
                ) {
                    current.setAttribute(
                        'data-simola-u20-section',
                        type
                    );

                    return current;
                }

                current = current.parentElement;
            }
        }

        return null;
    };

    const markKpi = (label, key) => {
        for (const labelNode of exactNodes(label)) {
            const card = findCardAncestor(labelNode);

            if (!card) {
                continue;
            }

            card.setAttribute(
                'data-simola-u20-kpi',
                key
            );

            labelNode.setAttribute(
                'data-simola-u20-kpi-label',
                '1'
            );

            const descendants =
                Array.from(
                    card.querySelectorAll(
                        'p,span,div,strong,b'
                    )
                )
                .filter((node) => {
                    if (
                        !(node instanceof HTMLElement) ||
                        node.children.length > 0
                    ) {
                        return false;
                    }

                    return /^\d[\d.,]*$/.test(
                        normalize(node.textContent)
                    );
                })
                .sort((a, b) => {
                    const aSize =
                        parseFloat(
                            window.getComputedStyle(a).fontSize
                        ) || 0;

                    const bSize =
                        parseFloat(
                            window.getComputedStyle(b).fontSize
                        ) || 0;

                    return bSize - aSize;
                });

            if (descendants[0]) {
                descendants[0].setAttribute(
                    'data-simola-u20-kpi-value',
                    '1'
                );
            }

            return card;
        }

        return null;
    };

    const markComparisonCards = (section) => {
        if (!section) {
            return;
        }

        [
            'Pelanggaran',
            'Kendala',
            'Accident',
            'Errorlog',
        ].forEach((label) => {
            exactNodes(label, section).forEach((node) => {
                const card = findCardAncestor(node);

                if (
                    card &&
                    section.contains(card)
                ) {
                    card.setAttribute(
                        'data-simola-u20-comparison-card',
                        normalize(label)
                    );
                }
            });
        });
    };

    const markAccentPreset = () => {
        const presetNames = [
            'corporate navy',
            'default biru',
            'indigo',
            'navy',
        ];

        Array.from(
            document.querySelectorAll(
                'button,a'
            )
        ).forEach((control) => {
            const text =
                normalize(
                    control.textContent
                );

            if (
                presetNames.includes(text) ||
                text.startsWith('aksen:')
            ) {
                control.setAttribute(
                    'data-simola-u20-accent-control',
                    '1'
                );

                if (
                    !text.startsWith('aksen:')
                ) {
                    control.dataset.simolaAccentOriginal =
                        control.textContent.trim();

                    control.textContent =
                        'Aksen: '
                        +
                        control.textContent.trim();
                }
            }
        });
    };

    const restoreAccentLabelIfNeeded = () => {
        if (root.classList.contains('dark')) {
            return;
        }

        document
            .querySelectorAll(
                '[data-simola-u20-accent-control]'
            )
            .forEach((control) => {
                if (
                    control.dataset.simolaAccentOriginal
                ) {
                    control.textContent =
                        control.dataset.simolaAccentOriginal;
                }
            });
    };

    const isDashboard = () =>
        exactNodes(
            'Dashboard Monitoring Utama'
        ).length > 0;

    const applyDashboard = () => {
        if (!isDashboard()) {
            return;
        }

        markKpi(
            'Pelanggaran',
            'pelanggaran'
        );

        markKpi(
            'Kendala',
            'kendala'
        );

        markKpi(
            'Accident',
            'accident'
        );

        markKpi(
            'Pengemudi Dinilai',
            'pengemudi'
        );

        markKpi(
            'Errorlog',
            'errorlog'
        );

        const comparison =
            findWideSection(
                'Perbandingan Bulanan',
                'comparison'
            );

        markComparisonCards(
            comparison
        );

        findWideSection(
            'Pengaturan Dashboard',
            'settings'
        );

        findWideSection(
            'Tren Monitoring Bulanan',
            'chart'
        );

        if (root.classList.contains('dark')) {
            markAccentPreset();
        }
        else {
            restoreAccentLabelIfNeeded();
        }
    };

    let queued = false;

    const scheduleApply = () => {
        if (queued) {
            return;
        }

        queued = true;

        window.requestAnimationFrame(() => {
            queued = false;
            applyDashboard();
        });
    };

    window.addEventListener(
        'DOMContentLoaded',
        () => {
            scheduleApply();
            window.setTimeout(scheduleApply, 180);
            window.setTimeout(scheduleApply, 600);
            window.setTimeout(scheduleApply, 1200);
        }
    );

    window.addEventListener(
        'simola-theme-changed',
        () => {
            scheduleApply();
            window.setTimeout(scheduleApply, 150);
        }
    );

    const observer =
        new MutationObserver(() => {
            if (isDashboard()) {
                scheduleApply();
            }
        });

    observer.observe(
        document.documentElement,
        {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: [
                'class',
                'style',
            ],
        }
    );
})();
/* === SIMOLA UNIFIED THEME JS v2.0 END === */

/* === SIMOLA DARK READABILITY JS v2.2 START === */
/* ==========================================================================
   SIMOLA DARK MODE READABILITY HARMONIZER v2.2
   Runtime contrast scanner for all pages.
   ========================================================================== */

(() => {
    if (window.__simolaReadabilityV22) {
        return;
    }

    window.__simolaReadabilityV22 = true;

    const root = document.documentElement;

    const blockSelector = [
        'main',
        'section',
        'article',
        'aside',
        'form',
        'div',
        'table',
        'thead',
        'tbody',
        'tr',
        'td',
        'th',
        'details',
        'summary'
    ].join(',');

    const textSelector = [
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
        'span',
        'label',
        'small',
        'strong',
        'b',
        'li',
        'dt',
        'dd',
        'th',
        'td',
        'a'
    ].join(',');

    const parseRgb = (value) => {
        const match = String(value || '').match(
            /rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+))?\s*\)/
        );

        if (!match) {
            return null;
        }

        return {
            r: Number(match[1]),
            g: Number(match[2]),
            b: Number(match[3]),
            a: match[4] === undefined
                ? 1
                : Number(match[4]),
        };
    };

    const linear = (channel) => {
        const value = channel / 255;

        return value <= 0.03928
            ? value / 12.92
            : Math.pow(
                (value + 0.055) / 1.055,
                2.4
            );
    };

    const luminance = (color) =>
        (
            0.2126 * linear(color.r)
            +
            0.7152 * linear(color.g)
            +
            0.0722 * linear(color.b)
        );

    const contrast = (first, second) => {
        const l1 = luminance(first);
        const l2 = luminance(second);
        const bright = Math.max(l1, l2);
        const dark = Math.min(l1, l2);

        return (bright + 0.05) / (dark + 0.05);
    };

    const effectiveBackground = (element) => {
        let current = element;

        while (
            current
            &&
            current instanceof HTMLElement
        ) {
            const style =
                window.getComputedStyle(
                    current
                );

            const color =
                parseRgb(
                    style.backgroundColor
                );

            if (
                color
                &&
                color.a >= 0.55
            ) {
                return color;
            }

            current =
                current.parentElement;
        }

        return {
            r: 11,
            g: 18,
            b: 32,
            a: 1,
        };
    };

    const isVisible = (element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        const rect =
            element.getBoundingClientRect();

        if (
            rect.width <= 0
            ||
            rect.height <= 0
        ) {
            return false;
        }

        const style =
            window.getComputedStyle(
                element
            );

        return (
            style.display !== 'none'
            &&
            style.visibility !== 'hidden'
            &&
            Number(style.opacity || 1) > 0
        );
    };

    const excludedRoot = (element) =>
        (
            element.closest('[data-simola-nav-menu]')
            ||
            element.closest('[data-simola-nav-wrapper]')
            ||
            element.closest('.simola-theme-toggle')
            ||
            element.closest('nav')
        );

    const semanticClass = (element) => {
        const className =
            String(
                element.className || ''
            );

        if (
            /\bbg-(green|emerald)-50\b/.test(className)
        ) {
            return 'success';
        }

        if (
            /\bbg-(yellow|amber|orange)-50\b/.test(className)
        ) {
            return 'warning';
        }

        if (
            /\bbg-(red|rose)-50\b/.test(className)
        ) {
            return 'danger';
        }

        if (
            /\bbg-(blue|indigo)-50\b/.test(className)
        ) {
            return 'info';
        }

        return null;
    };

    const isInteractiveSmall = (element) => {
        if (
            element.matches(
                'button,a,input,select,textarea,option,svg,path'
            )
        ) {
            return true;
        }

        const className =
            String(
                element.className || ''
            );

        return (
            className.includes('rounded-full')
            &&
            (
                className.includes('bg-')
                ||
                className.includes('px-')
            )
        );
    };

    const markSurfaces = () => {
        document
            .querySelectorAll(
                blockSelector
            )
            .forEach((element) => {
                if (
                    !(element instanceof HTMLElement)
                    ||
                    excludedRoot(element)
                    ||
                    isInteractiveSmall(element)
                    ||
                    !isVisible(element)
                ) {
                    return;
                }

                const rect =
                    element.getBoundingClientRect();

                if (
                    rect.width < 100
                    ||
                    rect.height < 30
                ) {
                    return;
                }

                const style =
                    window.getComputedStyle(
                        element
                    );

                const background =
                    parseRgb(
                        style.backgroundColor
                    );

                if (
                    !background
                    ||
                    background.a < 0.58
                ) {
                    return;
                }

                const semantic =
                    semanticClass(
                        element
                    );

                if (semantic) {
                    element.setAttribute(
                        'data-simola-v22-surface',
                        semantic
                    );

                    return;
                }

                if (
                    luminance(background)
                    >=
                    0.58
                ) {
                    const parentMarked =
                        element.parentElement
                        ?.closest(
                            '[data-simola-v22-surface="panel"],'
                            +
                            '[data-simola-v22-surface="nested"]'
                        );

                    element.setAttribute(
                        'data-simola-v22-surface',
                        parentMarked
                            ? 'nested'
                            : 'panel'
                    );
                }
            });
    };

    const hasSemanticText = (element) => {
        const className =
            String(
                element.className || ''
            );

        return (
            /\btext-(green|emerald|red|rose|yellow|amber|orange)-(500|600|700|800)\b/
                .test(className)
            ||
            (
                className.includes('rounded-full')
                &&
                className.includes('bg-')
            )
        );
    };

    const markText = () => {
        document
            .querySelectorAll(
                textSelector
            )
            .forEach((element) => {
                if (
                    !(element instanceof HTMLElement)
                    ||
                    excludedRoot(element)
                    ||
                    !isVisible(element)
                    ||
                    hasSemanticText(element)
                ) {
                    return;
                }

                /*
                 * Do not recolor text inside solid primary/danger buttons.
                 */
                const buttonLike =
                    element.closest(
                        'button[class*="bg-"],'
                        +
                        'a[class*="bg-"]'
                    );

                if (
                    buttonLike
                    &&
                    !buttonLike.className.includes(
                        'bg-white'
                    )
                ) {
                    return;
                }

                const style =
                    window.getComputedStyle(
                        element
                    );

                const foreground =
                    parseRgb(
                        style.color
                    );

                if (!foreground) {
                    return;
                }

                const background =
                    effectiveBackground(
                        element
                    );

                const ratio =
                    contrast(
                        foreground,
                        background
                    );

                const fontSize =
                    parseFloat(
                        style.fontSize || '16'
                    );

                const fontWeight =
                    parseInt(
                        style.fontWeight || '400',
                        10
                    );

                const largeText =
                    fontSize >= 18
                    ||
                    (
                        fontSize >= 14
                        &&
                        fontWeight >= 700
                    );

                const minimum =
                    largeText
                        ? 3.0
                        : 4.2;

                if (ratio >= minimum) {
                    return;
                }

                if (element.matches('a')) {
                    element.setAttribute(
                        'data-simola-v22-text',
                        'link'
                    );

                    return;
                }

                if (
                    element.matches(
                        'h1,h2,h3,h4,h5,h6,strong,b'
                    )
                    ||
                    fontWeight >= 600
                ) {
                    element.setAttribute(
                        'data-simola-v22-text',
                        'strong'
                    );

                    return;
                }

                if (
                    element.matches(
                        'small'
                    )
                    ||
                    fontSize <= 12
                ) {
                    element.setAttribute(
                        'data-simola-v22-text',
                        'muted'
                    );

                    return;
                }

                element.setAttribute(
                    'data-simola-v22-text',
                    'normal'
                );
            });
    };

    const chartInstances = () => {
        if (
            !window.Chart
            ||
            !window.Chart.instances
        ) {
            return [];
        }

        if (
            window.Chart.instances
            instanceof Map
        ) {
            return Array.from(
                window.Chart.instances.values()
            );
        }

        return Object.values(
            window.Chart.instances
        );
    };

    const syncCharts = () => {
        if (!window.Chart) {
            return;
        }

        const text = '#cbd5e1';
        const muted = '#9fb0c5';
        const grid = 'rgba(148, 163, 184, .16)';

        try {
            window.Chart.defaults.color =
                text;

            window.Chart.defaults.borderColor =
                grid;
        } catch (error) {
            // Compatible fallback: update existing charts only.
        }

        chartInstances()
            .forEach((chart) => {
                if (
                    !chart
                    ||
                    !chart.options
                ) {
                    return;
                }

                if (
                    chart.options.plugins
                    ?.legend
                    ?.labels
                ) {
                    chart.options.plugins
                        .legend
                        .labels
                        .color =
                        text;
                }

                if (
                    chart.options.plugins
                    ?.title
                ) {
                    chart.options.plugins
                        .title
                        .color =
                        text;
                }

                const scales =
                    chart.options.scales || {};

                Object.values(scales)
                    .forEach((scale) => {
                        if (!scale) {
                            return;
                        }

                        scale.ticks =
                            scale.ticks || {};

                        scale.ticks.color =
                            muted;

                        scale.grid =
                            scale.grid || {};

                        scale.grid.color =
                            grid;

                        scale.border =
                            scale.border || {};

                        scale.border.color =
                            grid;

                        if (scale.title) {
                            scale.title.color =
                                text;
                        }
                    });

                try {
                    chart.update('none');
                } catch (error) {
                    try {
                        chart.update();
                    } catch (ignored) {
                        // A custom wrapper may reject runtime updates.
                    }
                }
            });
    };

    const scan = () => {
        if (
            !root.classList.contains(
                'dark'
            )
        ) {
            return;
        }

        markSurfaces();

        /*
         * Let CSS apply the new panel background before checking text contrast.
         */
        window.requestAnimationFrame(
            () => {
                markText();
                syncCharts();
            }
        );
    };

    let scheduled = false;

    const schedule = () => {
        if (scheduled) {
            return;
        }

        scheduled = true;

        window.requestAnimationFrame(
            () => {
                scheduled = false;
                scan();
            }
        );
    };

    window.addEventListener(
        'DOMContentLoaded',
        () => {
            schedule();

            [
                100,
                300,
                700,
                1400,
            ].forEach(
                (delay) =>
                    window.setTimeout(
                        schedule,
                        delay
                    )
            );
        }
    );

    window.addEventListener(
        'simola-theme-changed',
        (event) => {
            if (
                event.detail?.theme
                ===
                'dark'
            ) {
                schedule();

                window.setTimeout(
                    schedule,
                    180
                );
            }
        }
    );

    const observer =
        new MutationObserver(
            () => {
                if (
                    root.classList.contains(
                        'dark'
                    )
                ) {
                    schedule();
                }
            }
        );

    observer.observe(
        document.documentElement,
        {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: [
                'class',
                'style',
            ],
        }
    );

    window.addEventListener(
        'resize',
        schedule,
        {
            passive: true,
        }
    );
})();
/* === SIMOLA DARK READABILITY JS v2.2 END === */

/* === SIMOLA LIGHT MODE ONLY JS v2.3.1 START === */
/* ==========================================================================
   SIMOLA LIGHT MODE ONLY ALIGNMENT v2.3.1
   Finds only theme/account wrappers. Does not rearrange primary nav items.
   ========================================================================== */

(() => {
    if (window.__simolaLightModeOnlyAlignmentV231) {
        return;
    }

    window.__simolaLightModeOnlyAlignmentV231 = true;

    const normalize = (value) =>
        String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

    const apply = () => {
        const nav =
            document.querySelector('nav.simola-navbar-surface')
            ||
            document.querySelector('.simola-navbar-surface');

        if (!nav) {
            return;
        }

        const toggle =
            nav.querySelector('[data-theme-toggle]');

        if (!toggle) {
            return;
        }

        let themeWrap =
            toggle.parentElement;

        /*
         * Climb only until the wrapper is a direct child of the main nav row.
         * We do not move any menu element.
         */
        for (let depth = 0; themeWrap && depth < 5; depth += 1) {
            const parent =
                themeWrap.parentElement;

            if (
                parent
                &&
                parent.querySelector('[data-theme-toggle]')
                &&
                Array.from(parent.children).some((child) => {
                    if (!(child instanceof HTMLElement)) {
                        return false;
                    }

                    const text =
                        normalize(child.textContent);

                    return (
                        text.includes('dashboard')
                        ||
                        text.includes('master fleet')
                        ||
                        text.includes('admin')
                    );
                })
            ) {
                break;
            }

            themeWrap =
                parent;
        }

        if (!(themeWrap instanceof HTMLElement)) {
            return;
        }

        themeWrap.setAttribute(
            'data-simola-lightmode-wrap',
            '1'
        );

        const row =
            themeWrap.parentElement;

        if (!row) {
            return;
        }

        const siblings =
            Array.from(row.children)
                .filter(
                    (child) =>
                        child instanceof HTMLElement
                );

        const account =
            siblings.find((child) => {
                if (child === themeWrap) {
                    return false;
                }

                const text =
                    normalize(child.textContent);

                return (
                    /\badmin\b/.test(text)
                    ||
                    /\bdeveloper\b/.test(text)
                );
            });

        if (account) {
            account.setAttribute(
                'data-simola-account-after-theme',
                '1'
            );
        }
    };

    window.addEventListener(
        'DOMContentLoaded',
        () => {
            apply();
            window.setTimeout(apply, 150);
            window.setTimeout(apply, 500);
        }
    );
})();
/* === SIMOLA LIGHT MODE ONLY JS v2.3.1 END === */
