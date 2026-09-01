(() => {
  "use strict";

  const FORM_SELECTOR = "[data-unsaved-changes]";
  const LINK_SELECTOR = 'a[href]';

  let dirty = false;
  let allowNavigation = false;

  const watchedForms = () =>
    Array.from(document.querySelectorAll(FORM_SELECTOR));

  const hasUserChanges = () =>
    watchedForms().some((form) => form.dataset.dirty === "true");

  const markDirty = (form) => {
    form.dataset.dirty = "true";
    dirty = true;
  };

  const clearDirty = () => {
    watchedForms().forEach((form) => {
      form.dataset.dirty = "false";
    });

    dirty = false;
  };

  const isWizardStepNavigation = (link) => {
    if (!link.closest(".wizard")) {
      return false;
    }

    const href = link.getAttribute("href") || "";

    try {
      const url = new URL(href, window.location.origin);

      return (
        url.origin === window.location.origin
        && url.pathname === "/members/create"
        && url.searchParams.has("step")
      );
    } catch (_) {
      return false;
    }
  };

  const isSafeImmediateNavigation = (link) => {
    if (isWizardStepNavigation(link)) {
      return true;
    }

    if (link.target && link.target !== "_self") {
      return true;
    }

    if (link.hasAttribute("download")) {
      return true;
    }

    const href = link.getAttribute("href") || "";

    return (
      href === ""
      || href.startsWith("#")
      || href.startsWith("javascript:")
    );
  };

  const beforeUnload = (event) => {
    if (allowNavigation || !hasUserChanges()) {
      return;
    }

    event.preventDefault();
    event.returnValue = "";
  };

  const initialize = () => {
    watchedForms().forEach((form) => {
      form.dataset.dirty = "false";

      form.addEventListener("input", () => {
        markDirty(form);
      });

      form.addEventListener("change", () => {
        markDirty(form);
      });

      form.addEventListener("submit", () => {
        // Form submission is an intentional save/navigation action.
        allowNavigation = true;
        clearDirty();
      });
    });

    document.addEventListener(
      "click",
      (event) => {
        const link = event.target.closest?.(LINK_SELECTOR);

        if (!link) {
          return;
        }

        // Wizard step tabs are intentional navigation. Do not show the
        // unsaved-changes prompt and, importantly, mark this navigation as
        // allowed so the browser's beforeunload handler does not prompt either.
        if (isWizardStepNavigation(link)) {
          allowNavigation = true;
          clearDirty();
          return;
        }

        if (isSafeImmediateNavigation(link)) {
          return;
        }

        if (!hasUserChanges()) {
          return;
        }

        const message =
          "You have unsaved changes. Leave this page and discard them?";

        if (!window.confirm(message)) {
          event.preventDefault();
          event.stopPropagation();
          return;
        }

        allowNavigation = true;
        clearDirty();
      },
      true
    );

    window.addEventListener(
      "beforeunload",
      beforeUnload
    );

    // Native browser history navigation (Back/Forward) is covered by
    // beforeunload. Keep this state resettable after BFCache restores.
    window.addEventListener("pageshow", () => {
      allowNavigation = false;
    });
  };

  document.addEventListener(
    "DOMContentLoaded",
    initialize
  );

  window.AcesUnsavedChanges = {
    markClean: () => {
      allowNavigation = true;
      clearDirty();
    },
  };
})();
