(() => {
    'use strict';

    const DEBOUNCE_MS = 300;

    const submitSearch = (input) => {
        const form = input.closest('form');

        if (!form) {
            return;
        }

        /*
         * Search must be performed by the server because the page is paginated.
         * The server filters the complete dataset before applying LIMIT/OFFSET.
         * Resetting the page is essential: the previous page may be outside the
         * result set for the new query.
         */
        const pageField = form.querySelector(
            'input[name="page"]'
        );

        if (pageField) {
            pageField.remove();
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    };

    const clearSearch = (input) => {
        input.value = '';

        submitSearch(input);
    };

    document.addEventListener('DOMContentLoaded', () => {
        document
            .querySelectorAll('[data-live-search]')
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
                        clearSearch(input);
                    }
                });
            });
    });
})();
