(() => {
  "use strict";

  const button = document.querySelector("[data-submit-loan-review]");
  const message = document.querySelector("[data-review-message]");

  if (!button) {
    return;
  }

  button.addEventListener("click", () => {
    /*
     * Submission will be wired to LoanService::create() in the next step.
     * For this phase, keep the action intentionally non-destructive.
     */
    message.textContent =
      "The application review is complete. Submission will be connected to the loan workflow next.";
    message.hidden = false;
  });
})();
