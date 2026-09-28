console.log("ACES App Loaded");

/* Data-table truncation tooltips: expose the full value only when text is actually clipped. */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.table__truncate').forEach((cell) => {
    const syncTooltip = () => {
      const text = cell.textContent.trim();
      if (text && cell.scrollWidth > cell.clientWidth) {
        cell.title = text;
      } else if (cell.dataset.tableTitle) {
        cell.title = cell.dataset.tableTitle;
      }
    };

    if (cell.title) {
      cell.dataset.tableTitle = cell.title;
    }

    syncTooltip();
    window.addEventListener('resize', syncTooltip, { passive: true });
  });
});

/* Format monetary inputs for display without changing the numeric value sent to PHP. */
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-money-input]").forEach((input) => {
    const format = () => {
      const raw = input.value.replace(/,/g, "").trim();

      if (raw === "") {
        return;
      }

      const normalized = raw.replace(/[^\d.]/g, "");
      const parts = normalized.split(".");

      if (!parts[0]) {
        input.value = "";
        return;
      }

      const integerPart = parts[0].replace(/^0+(?=\d)/, "");
      const decimalPart = parts[1] !== undefined
        ? parts[1].slice(0, 2)
        : "";

      input.value =
        Number(integerPart).toLocaleString("en-US")
        + (parts.length > 1 ? `.${decimalPart}` : "");
    };

    input.addEventListener("input", format);

    input.form?.addEventListener("submit", () => {
      input.value = input.value.replace(/,/g, "");
    });
  });
});
