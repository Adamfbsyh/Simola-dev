<script>
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.querySelector('nav');

    if (!nav || nav.dataset.simolaGroupedNav === '1') {
        return;
    }

    nav.dataset.simolaGroupedNav = '1';

    const normalizeText = (value) =>
        String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

    const allLinks = Array.from(nav.querySelectorAll('a'));

    const findLinks = (label) =>
        allLinks.filter((link) => normalizeText(link.textContent) === normalizeText(label));

    const sameParentPairs = (firstLinks, secondLinks) => {
        const pairs = [];

        firstLinks.forEach((first) => {
            const second = secondLinks.find((candidate) => candidate.parentElement === first.parentElement);

            if (second) {
                pairs.push([first, second]);
            }
        });

        return pairs;
    };

    const looksLikeDesktopContainer = (element) => {
        if (!element) {
            return false;
        }

        const className = String(element.className || '');

        return (
            className.includes('sm:flex') ||
            className.includes('md:flex') ||
            className.includes('lg:flex') ||
            className.includes('space-x') ||
            className.includes('gap-')
        );
    };

    const isCurrentLink = (link) => {
        if (!link || !link.href) {
            return false;
        }

        try {
            const target = new URL(link.href, window.location.origin);
            const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
            const targetPath = target.pathname.replace(/\/+$/, '') || '/';

            return (
                currentPath === targetPath ||
                link.getAttribute('aria-current') === 'page'
            );
        } catch (error) {
            return false;
        }
    };

    const closeAllDropdowns = (exceptWrapper) => {
        nav.querySelectorAll('[data-simola-nav-menu]').forEach((menu) => {
            const wrapper = menu.closest('[data-simola-nav-wrapper]');

            if (exceptWrapper && wrapper === exceptWrapper) {
                return;
            }

            menu.classList.add('hidden');

            const button = wrapper
                ? wrapper.querySelector('[data-simola-nav-button]')
                : null;

            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const makeDropdown = (firstLink, secondLink, parentLabel, childLabels) => {
        const parent = firstLink.parentElement;

        if (!parent || parent !== secondLink.parentElement) {
            return;
        }

        if (!looksLikeDesktopContainer(parent)) {
            return;
        }

        if (
            firstLink.dataset.simolaGrouped === '1' ||
            secondLink.dataset.simolaGrouped === '1'
        ) {
            return;
        }

        firstLink.dataset.simolaGrouped = '1';
        secondLink.dataset.simolaGrouped = '1';

        const firstActive = isCurrentLink(firstLink);
        const secondActive = isCurrentLink(secondLink);
        const active = firstActive || secondActive;

        const wrapper = document.createElement('div');
        wrapper.className = 'relative flex self-stretch items-center';
        wrapper.setAttribute('data-simola-nav-wrapper', '1');

        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-simola-nav-button', '1');
        button.setAttribute('aria-expanded', 'false');
        button.className = [
            'inline-flex',
            'h-full',
            'items-center',
            'gap-1.5',
            'border-b-2',
            'px-1',
            'pt-1',
            'text-sm',
            'font-medium',
            'transition',
            'focus:outline-none'
        ].concat(
            active
                ? ['border-indigo-500', 'text-slate-900']
                : [
                    'border-transparent',
                    'text-slate-500',
                    'hover:border-slate-300',
                    'hover:text-slate-700'
                ]
        ).join(' ');

        const label = document.createElement('span');
        label.textContent = parentLabel;

        const caret = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        caret.setAttribute('viewBox', '0 0 20 20');
        caret.setAttribute('fill', 'currentColor');
        caret.setAttribute('aria-hidden', 'true');
        caret.classList.add('h-4', 'w-4', 'transition-transform');
        caret.innerHTML =
            '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>';

        button.appendChild(label);
        button.appendChild(caret);

        const menu = document.createElement('div');
        menu.className =
            'absolute left-0 top-full z-50 mt-1 hidden min-w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5';
        menu.setAttribute('data-simola-nav-menu', '1');

        [
            [firstLink, childLabels[0]],
            [secondLink, childLabels[1]]
        ].forEach(([sourceLink, childLabel]) => {
            const item = document.createElement('a');
            item.href = sourceLink.href;
            item.textContent = childLabel;
            item.className = [
                'block',
                'px-4',
                'py-2.5',
                'text-sm',
                'font-medium',
                'transition'
            ].concat(
                isCurrentLink(sourceLink)
                    ? ['bg-indigo-50', 'text-indigo-700']
                    : ['text-slate-700', 'hover:bg-slate-50', 'hover:text-slate-900']
            ).join(' ');

            menu.appendChild(item);
        });

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const opening = menu.classList.contains('hidden');

            closeAllDropdowns(wrapper);

            if (opening) {
                menu.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
                caret.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
                caret.classList.remove('rotate-180');
            }
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
                caret.classList.remove('rotate-180');
            }
        });

        wrapper.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
                caret.classList.remove('rotate-180');
                button.focus();
            }
        });

        parent.insertBefore(wrapper, firstLink);
        wrapper.appendChild(button);
        wrapper.appendChild(menu);

        firstLink.remove();
        secondLink.remove();
    };

    const groups = [
        {
            first: 'Upload Terpadu',
            second: 'Riwayat Upload',
            parent: 'Upload Terpadu',
            children: ['Upload Terpadu', 'Riwayat Upload']
        },
        {
            first: 'Crosscheck K3.2',
            second: 'Laporan K3.2',
            parent: 'Crosscheck K3.2',
            children: ['Crosscheck K3.2', 'Laporan K3.2']
        }
    ];

    groups.forEach((group) => {
        const pairs = sameParentPairs(
            findLinks(group.first),
            findLinks(group.second)
        );

        pairs.forEach(([first, second]) => {
            makeDropdown(
                first,
                second,
                group.parent,
                group.children
            );
        });
    });
});
</script>
