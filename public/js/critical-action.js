(() => {
  "use strict";

  const MODAL_ID = "aces-critical-action-modal";
  const SUCCESS_CLOSE_LABEL = "Continue";
  const MIN_LOADING_MS = 3000;

  const EXCLUDED_ACTIONS = [
    /^\/login(?:$|\/|\?)/i,
    /^\/members\/create(?:$|\?|\/)/i,
    /^\/members\/register(?:$|\?|\/)/i,
  ];

  const ACTION_LABELS = [
    [/^\/members\/status(?:$|\/|\?)/i, "Updating member status..."],
    [/^\/members\/beneficiaries\/delete(?:$|\/|\?)/i, "Removing beneficiary..."],
    [/^\/members\/beneficiaries\/update(?:$|\/|\?)/i, "Updating beneficiary..."],
    [/^\/members\/beneficiaries(?:$|\/|\?)/i, "Adding beneficiary..."],
    [/^\/loans\/payments\/[^/]+\/reverse(?:$|\/|\?)/i, "Reversing payment..."],
    [/^\/loans\/[^/]+\/submit(?:$|\/|\?)/i, "Submitting loan application..."],
    [/^\/loans\/[^/]+\/release(?:$|\/|\?)/i, "Releasing loan..."],
    [/^\/loans\/[^/]+\/approve(?:$|\/|\?)/i, "Approving loan..."],
    [/^\/loans\/[^/]+\/reject(?:$|\/|\?)/i, "Rejecting loan..."],
    [/^\/loans\/[^/]+\/payments(?:$|\/|\?)/i, "Recording payment..."],
    [/^\/loans\/create(?:$|\?|\/)/i, "Saving loan application..."],
    [/^\/ledger\/[^/]+\/approve(?:$|\/|\?)/i, "Approving voucher..."],
    [/^\/ledger\/[^/]+\/reject(?:$|\/|\?)/i, "Rejecting voucher..."],
    [/^\/ledger\/[^/]+\/post(?:$|\/|\?)/i, "Posting voucher..."],
  ];

  let activeForm = null;
  let activeResponseUrl = null;
  let processing = false;
  let previousFocusedElement = null;

  const getFocusableElements = (container) => {
    if (!container) {
      return [];
    }

    return Array.from(
      container.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), ' +
        'select:not([disabled]), textarea:not([disabled]), ' +
        '[tabindex]:not([tabindex="-1"])'
      )
    ).filter((element) => {
      return !element.hidden
        && element.getAttribute("aria-hidden") !== "true"
        && element.getClientRects().length > 0;
    });
  };

  const trapModalFocus = (event) => {
    const modal = document.getElementById(MODAL_ID);

    if (
      !modal
      || modal.hidden
      || !modal.classList.contains("is-open")
    ) {
      return;
    }

    const dialog = modal.querySelector(
      ".critical-action-modal__dialog"
    );

    if (!dialog) {
      return;
    }

    if (event.key === "Escape") {
      if (!processing) {
        event.preventDefault();
        closeModal(true);
      }
      return;
    }

    if (event.key !== "Tab") {
      return;
    }

    const focusable = getFocusableElements(dialog);

    if (focusable.length === 0) {
      event.preventDefault();
      dialog.focus({ preventScroll: true });
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus({ preventScroll: true });
      return;
    }

    if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus({ preventScroll: true });
    }
  };

  const getActionPath = (form) => {
    const action = form.getAttribute("action") || window.location.href;
    return new URL(action, window.location.origin).pathname;
  };

  const isExcluded = (form) => {
    if (form.matches("[data-no-critical-modal]")) {
      return true;
    }

    if ((form.method || "GET").toUpperCase() !== "POST") {
      return true;
    }

    if (form.matches("[data-registration-form]")) {
      return true;
    }

    const path = getActionPath(form);
    return EXCLUDED_ACTIONS.some((pattern) => pattern.test(path));
  };

  const actionMessage = (form) => {
    const path = getActionPath(form);

    for (const [pattern, message] of ACTION_LABELS) {
      if (pattern.test(path)) {
        return message;
      }
    }

    const submit = form.querySelector(
      'button[type="submit"], input[type="submit"]'
    );

    const buttonText = (
      submit?.textContent || submit?.value || ""
    ).trim();

    if (buttonText) {
      return `${buttonText.replace(/\.+$/, "")}...`;
    }

    return "Processing your request...";
  };

  const ensureModal = () => {
    let modal = document.getElementById(MODAL_ID);

    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.id = MODAL_ID;
    modal.className = "critical-action-modal";
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");

    modal.innerHTML = `
      <div
        class="critical-action-modal__backdrop"
        aria-hidden="true"></div>

      <section
        class="critical-action-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="critical-action-modal-title"
        aria-describedby="critical-action-modal-message"
        tabindex="-1">

        <div class="critical-action-modal__main">
          <div
            class="critical-action-modal__spinner"
            data-critical-spinner
            aria-hidden="true"></div>

          <div class="critical-action-modal__content">
            <strong id="critical-action-modal-title">
              Processing...
            </strong>

            <span id="critical-action-modal-message">
              Please wait while the operation is completed.
            </span>
          </div>
        </div>

        <div class="critical-action-modal__actions">
          <button
            type="button"
            class="critical-action-modal__retry"
            data-critical-retry
            hidden>
            Try Again
          </button>

          <button
            type="button"
            class="critical-action-modal__close"
            data-critical-close
            disabled>
            ${SUCCESS_CLOSE_LABEL}
          </button>
        </div>
      </section>
    `;

    document.body.appendChild(modal);

    modal.querySelector("[data-critical-close]")?.addEventListener(
      "click",
      (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (processing) {
          return;
        }

        closeModal(true);
      }
    );

    modal.querySelector("[data-critical-retry]")?.addEventListener(
      "click",
      (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (processing || !activeForm) {
          return;
        }

        processForm(activeForm);
      }
    );

    return modal;
  };

  const setPageLocked = (locked) => {
    const app = document.querySelector(".app");

    if (locked) {
      app?.setAttribute("inert", "");
      document.body.classList.add("critical-action-is-processing");
      return;
    }

    app?.removeAttribute("inert");
    document.body.classList.remove("critical-action-is-processing");
  };

  const setState = ({ title, message, loading, canClose, error }) => {
    const modal = ensureModal();
    const spinner = modal.querySelector("[data-critical-spinner]");
    const close = modal.querySelector("[data-critical-close]");
    const retry = modal.querySelector("[data-critical-retry]");
    const titleNode = modal.querySelector("#critical-action-modal-title");
    const messageNode = modal.querySelector("#critical-action-modal-message");

    titleNode.textContent = title;
    messageNode.textContent = message;
    spinner.hidden = !loading;
    close.disabled = !canClose;
    retry.hidden = loading || !error;
    modal.classList.toggle("is-complete", !loading && !error);
    modal.classList.toggle("is-error", Boolean(error));
  };

  const openModal = (form) => {
    const modal = ensureModal();
    const dialog = modal.querySelector(
      ".critical-action-modal__dialog"
    );

    previousFocusedElement = document.activeElement;
    activeForm = form;
    activeResponseUrl = null;

    setState({
      title: actionMessage(form),
      message: "Please wait while the operation is completed.",
      loading: true,
      canClose: false,
      error: false,
    });

    modal.hidden = false;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    setPageLocked(true);
    dialog?.focus({ preventScroll: true });
  };

  const closeModal = (followResponse) => {
    const modal = ensureModal();
    const destination = activeResponseUrl;

    modal.hidden = true;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("is-complete", "is-error");
    setPageLocked(false);

    const focusTarget = previousFocusedElement;
    previousFocusedElement = null;

    activeForm = null;
    processing = false;

    if (
      !followResponse
      && focusTarget instanceof HTMLElement
      && document.contains(focusTarget)
    ) {
      focusTarget.focus({ preventScroll: true });
    }

    if (followResponse && destination) {
      if (destination === window.location.href) {
        window.location.reload();
        return;
      }

      window.location.assign(destination);
    }
  };

  const getRenderedErrorMessage = async (response) => {
    if (
      !response.url
      || !response.headers
        .get("content-type")
        ?.includes("text/html")
    ) {
      return "";
    }

    // A successful workflow redirect is authoritative. Do not mistake an
    // unrelated/stale alert rendered on the destination page for a failed
    // request.
    try {
      const finalUrl = new URL(response.url);

      if (
        finalUrl.searchParams.has("success")
        && finalUrl.searchParams.get("success") !== ""
      ) {
        return "";
      }
    } catch (_) {
      // Fall through to HTML inspection if the URL cannot be parsed.
    }

    const text = await response.clone().text();

    const documentFragment =
      new DOMParser().parseFromString(
        text,
        "text/html"
      );

    const errorNode =
      documentFragment.querySelector(
        ".alert--error, [role=\"alert\"]"
      );

    return errorNode?.textContent
      ?.replace(/\s+/g, " ")
      .trim()
      || "";
  };

  const processForm = async (form) => {
    if (processing) {
      return;
    }

    processing = true;
    openModal(form);

    const submitButtons = form.querySelectorAll(
      'button[type="submit"], input[type="submit"]'
    );

    submitButtons.forEach((button) => {
      button.disabled = true;
    });

    try {
      const requestStartedAt = performance.now();

      const response = await fetch(
        form.action || window.location.href,
        {
          method: form.method || "POST",
          body: new FormData(form),
          credentials: "same-origin",
          redirect: "follow",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      const elapsed =
        performance.now() - requestStartedAt;

      if (elapsed < MIN_LOADING_MS) {
        await new Promise((resolve) => {
          window.setTimeout(
            resolve,
            MIN_LOADING_MS - elapsed
          );
        });
      }

      activeResponseUrl = response.url || window.location.href;

      const renderedError =
        await getRenderedErrorMessage(response);

      if (!response.ok || renderedError) {
        const details =
          renderedError
          || `The server returned HTTP ${response.status}.`;

        throw new Error(details);
      }

      setState({
        title: "Operation complete",
        message: "The requested changes were saved successfully.",
        loading: false,
        canClose: true,
        error: false,
      });

      processing = false;
      modalFocus();
    } catch (error) {
      console.error("Critical action failed.", error);

      setState({
        title: "Something went wrong",
        message:
          error instanceof Error && error.message
            ? error.message
            : "The operation could not be completed. Check your connection and try again.",
        loading: false,
        canClose: true,
        error: true,
      });

      processing = false;
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
      modalFocus();
    }
  };

  const modalFocus = () => {
    const modal = ensureModal();
    const close = modal.querySelector("[data-critical-close]");
    const dialog = modal.querySelector(
      ".critical-action-modal__dialog"
    );

    if (!close.disabled) {
      close.focus({ preventScroll: true });
      return;
    }

    dialog?.focus({ preventScroll: true });
  };

  document.addEventListener(
    "submit",
    (event) => {
      const form = event.target;

      if (!(form instanceof HTMLFormElement) || isExcluded(form)) {
        return;
      }

      event.preventDefault();
      processForm(form);
    },
    true
  );
})();


  document.addEventListener(
    "keydown",
    trapModalFocus,
    true
  );
