ACES QA/RBAC patch — 2026-09-28

Source:
  aces-next(20260928-013217).zip

Changes:
1. Loan member picker
   - Removed helper text under Member.
   - Added a structured, readable dropdown layout for member search results.
2. Amortization preview
   - Removed the automatic "N payment periods generated automatically." label.
3. Loan details page
   - Removed "Review the application and take the next workflow action.".
   - Consolidated the page status into one badge placed immediately to the left of Loan ID.
   - Removed duplicate workflow status badge/heading and decision-section status label.
4. Loan Officer authorization
   - Approve and Reject POST routes now use LoanOfficerMiddleware, allowing admin and loan_officer.
   - The existing Loan Officer/Admin UI gate remains in place.

Files changed:
  routes/web.php
  resources/views/loans/create.php
  resources/views/loans/show.php
  public/js/loan-create.js
  public/css/pages/loans.css

Validation:
  PHP syntax checks passed for all changed PHP files.
  Node syntax check passed for loan-create.js.

No database migration is required for this patch.
