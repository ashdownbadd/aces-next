(() => {
    "use strict";

    const DEBOUNCE_MS = 350;

    let timer = null;
    let controller = null;

    const replaceFromResponse = (selector, documentFragment) => {
        const current = document.querySelector(selector);
        const incoming = documentFragment.querySelector(selector);

        if (!current) {
            return;
        }

        if (!incoming) {
            current.innerHTML = "";
            return;
        }

        current.replaceChildren(
            ...Array.from(incoming.childNodes).map(
                (node) => node.cloneNode(true)
            )
        );
    };

    const search = async (input) => {
        const form = input.closest("form");

        if (!form) {
            return;
        }

        const url = new URL(
            form.action || window.location.href,
            window.location.origin
        );

        url.search = "";

        for (const [key, value] of new FormData(form).entries()) {
            if (typeof value === "string" && value !== "") {
                url.searchParams.set(key, value);
            }
        }

        url.searchParams.delete("page");

        controller?.abort();
        controller = new AbortController();

        try {
            const response = await fetch(url.toString(), {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Search request failed: ${response.status}`
                );
            }

            const html = await response.text();

            if (controller.signal.aborted) {
                return;
            }

            const incoming =
                new DOMParser().parseFromString(
                    html,
                    "text/html"
                );

            const target =
                input.dataset.liveSearchTarget;

            if (target) {
                replaceFromResponse(
                    target,
                    incoming
                );
            }

            [
                ".members__footer",
                ".loan-list__footer",
                ".ledger-page__footer",
                ".activity-logs__footer",
            ].forEach((selector) => {
                replaceFromResponse(
                    selector,
                    incoming
                );
            });

            window.history.replaceState(
                {},
                "",
                url.toString()
            );
        } catch (error) {
            if (error.name !== "AbortError") {
                console.error(
                    "Live search failed.",
                    error
                );
            }
        }
    };

    const queue = (input) => {
        window.clearTimeout(timer);

        timer = window.setTimeout(
            () => search(input),
            DEBOUNCE_MS
        );
    };

    document.addEventListener(
        "DOMContentLoaded",
        () => {
            document
                .querySelectorAll(
                    'input[data-live-search]'
                )
                .forEach((input) => {
                    input.addEventListener(
                        "input",
                        () => queue(input)
                    );

                    input.addEventListener(
                        "keydown",
                        (event) => {
                            if (
                                event.key !== "Escape"
                            ) {
                                return;
                            }

                            window.clearTimeout(timer);
                            input.value = "";
                            queue(input);
                        }
                    );
                }
            );
        }
    );
})();
