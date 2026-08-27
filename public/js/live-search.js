(() => {
    'use strict';

    const DEBOUNCE_MS = 300;

    const submitSearch = async (input) => {
        const form = input.closest('form');
        const targetSelector = input.dataset.liveSearchTarget;

        if (!form || !targetSelector) {
            return;
        }

        const url = new URL(form.action || window.location.href, window.location.origin);
        const formData = new FormData(form);

        url.search = '';

        for (const [key, value] of formData.entries()) {
            if (typeof value === 'string' && value !== '') {
                url.searchParams.set(key, value);
            }
        }

        // Search changes must always begin on page 1.
        url.searchParams.delete('page');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Search request failed: ${response.status}`);
            }

            const html = await response.text();
            const documentFragment = new DOMParser().parseFromString(
                html,
                'text/html'
            );

            const nextTarget = documentFragment.querySelector(
                targetSelector
            );
            const currentTarget = document.querySelector(
                targetSelector
            );

            if (nextTarget && currentTarget) {
                currentTarget.replaceChildren(
                    ...Array.from(nextTarget.childNodes).map(
                        (node) => node.cloneNode(true)
                    )
                );
            } else if (!nextTarget && currentTarget) {
                currentTarget.replaceChildren();
            }

            // Keep the result range/pagination synchronized without touching
            // the input itself, so typing and Ctrl+A behave normally.
            [
                '.members__footer',
                '.members__pagination',
                '.loan-list__footer',
                '.loan-list__pagination',
                '.ledger-page__footer',
                '.activity-logs__footer',
            ].forEach((selector) => {
                const current = document.querySelector(selector);
                const incoming = documentFragment.querySelector(selector);

                if (current && incoming) {
                    current.replaceWith(incoming.cloneNode(true));
                } else if (current && !incoming) {
                    current.remove();
                }
            });
        } catch (error) {
            console.error('Live search failed:', error);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document
            .querySelectorAll('input[data-live-search]')
            .forEach((input) => {
                let timer = null;

                input.addEventListener('input', () => {
                    window.clearTimeout(timer);

                    timer = window.setTimeout(() => {
                        submitSearch(input);
                    }, DEBOUNCE_MS);
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        window.clearTimeout(timer);
                        input.value = '';
                        submitSearch(input);
                    }
                });
            });
    });
})();
