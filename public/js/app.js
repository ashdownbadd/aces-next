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
