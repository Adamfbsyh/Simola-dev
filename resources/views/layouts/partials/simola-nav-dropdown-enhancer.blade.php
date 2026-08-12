<script>
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.querySelector('nav');

    if (!nav || nav.dataset.simolaGroupedNav === '1') {
        return;
    }

    nav.dataset.simolaGroupedNav = '1';

    const normalize = (value) =>
        String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

    const links = Array.from(nav.querySelectorAll('a'));

    const find = (label) =>
        links.filter((link) => normalize(link.textContent) === normalize(label));

    const isCurrent = (link) => {
        if (!link || !link.href) {
            return false;
        }

        try {
            const target = new URL(link.href, window.location.origin);
            const current = window.location.pathname.replace(/\/+$/, '') || '/';
            const path = target.pathname.replace(/\/+$/, '') || '/';

            return current === path || link.getAttribute('aria-current') === 'page';
        } catch (error) {
            return false;
        }
    };

    const desktopContainer = (element) => {
        if (!element) {
            return false;
        }

        const classes = String(element.className || '');

        return (
            classes.includes('sm:flex') ||
            classes.includes('md:flex') ||
            classes.includes('lg:flex') ||
            classes.includes('space-x') ||
            classes.includes('gap-')
        );
    };

    const close = (except = null) => {
        nav.querySelectorAll('[data-simola-nav-wrapper]').forEach((wrapper) => {
            if (wrapper === except) {
                return;
            }

            const menu = wrapper.querySelector('[data-simola-nav-menu]');
            const button = wrapper.querySelector('[data-simola-nav-button]');

            if (menu) {
                menu.classList.add('hidden');
            }

            if (button) {
                button.setAttribute('aria-expanded', 'false');

                const caret = button.querySelector('[data-simola-nav-caret]');
                caret?.classList.remove('rotate-180');
            }
        });
    };

    const createGroup = (first, second, parentLabel, childLabels) => {
        const parent = first?.parentElement;

        if (!parent || parent !== second?.parentElement || !desktopContainer(parent)) {
            return;
        }

        if (first.dataset.simolaGrouped === '1' || second.dataset.simolaGrouped === '1') {
            return;
        }

        first.dataset.simolaGrouped = '1';
        second.dataset.simolaGrouped = '1';

        const active = isCurrent(first) || isCurrent(second);

        const wrapper = document.createElement('div');
        wrapper.className = 'relative flex self-stretch items-center';
        wrapper.dataset.simolaNavWrapper = '1';

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.simolaNavButton = '1';
        button.setAttribute('aria-haspopup', 'menu');
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
            'focus:outline-none',
            active
                ? 'border-indigo-500 text-slate-900'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
        ].join(' ');

        const label = document.createElement('span');
        label.textContent = parentLabel;

        const caret = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        caret.dataset.simolaNavCaret = '1';
        caret.setAttribute('viewBox', '0 0 20 20');
        caret.setAttribute('fill', 'currentColor');
        caret.setAttribute('aria-hidden', 'true');
        caret.classList.add('h-4', 'w-4', 'transition-transform');
        caret.innerHTML =
            '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>';

        button.append(label, caret);

        const menu = document.createElement('div');
        menu.dataset.simolaNavMenu = '1';
        menu.setAttribute('role', 'menu');
        menu.className = 'hidden';

        [
            [first, childLabels[0]],
            [second, childLabels[1]]
        ].forEach(([source, childLabel]) => {
            const item = document.createElement('a');
            item.href = source.href;
            item.textContent = childLabel;
            item.setAttribute('role', 'menuitem');

            if (isCurrent(source)) {
                item.dataset.active = '1';
            }

            menu.appendChild(item);
        });

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const opening = menu.classList.contains('hidden');

            close(wrapper);

            menu.classList.toggle('hidden', !opening);
            button.setAttribute('aria-expanded', opening ? 'true' : 'false');
            caret.classList.toggle('rotate-180', opening);
        });

        wrapper.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            menu.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
            caret.classList.remove('rotate-180');
            button.focus();
        });

        document.addEventListener('click', function (event) {
            if (wrapper.contains(event.target)) {
                return;
            }

            menu.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
            caret.classList.remove('rotate-180');
        });

        parent.insertBefore(wrapper, first);
        wrapper.append(button, menu);

        first.remove();
        second.remove();
    };

    [
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
    ].forEach((group) => {
        find(group.first).forEach((first) => {
            const second = find(group.second)
                .find((candidate) => candidate.parentElement === first.parentElement);

            if (!second) {
                return;
            }

            createGroup(
                first,
                second,
                group.parent,
                group.children
            );
        });
    });
});
</script>
