# Changelog

Notable changes to the BMPC Project Management System. Newest first.

## 2026-10-25

### Added
- **Expense reversals**: Finance corrects a wrong expense by reversing it with a reason. A matching negative entry is added and the original stays visible, marked "Reversed". Recorded expenses are never edited or deleted, and an expense can be reversed only once.
- **Budget check before approval**: each pending budget request shows the project's remaining budget after approval, or "Over budget by ₱…" in red.
- README sections on recovery targets (RPO/RTO), capacity and performance targets, data ownership, and third-party services that receive data.

### Changed
- Deactivating a user now signs them out on their next click instead of when their session ends.

## 2026-10-23

### Added
- **Notification preferences** in Profile: each user chooses which notifications appear under the bell. Budget-exceeded and project-assignment notifications are always delivered.
- **Friendly error pages** for 404 (not found), 419 (session expired), 500 (server error), and 503 (maintenance), matching the existing 403 and 429 pages.
- **Signature lines** ("Prepared by", "Noted by", "Approved by") on PDF reports.
- **HSTS and Permissions-Policy** security headers in the production nginx config.
- README with setup, roles, environment variables, deployment, and backup guidance; this changelog.

### Changed
- Only **contractor** personnel submit budget requests. In-house personnel (OIC, Staff, Branch Manager, Foreman) have no Budget Request tab; Finance records their project's expenses.
- Budget-request decisions lock the record, so two decisions sent at the same moment can't both apply.
- Submit buttons are disabled while a form is being sent, preventing double submissions.

## 2026-10-22

### Added
- **Breadcrumbs** on pages inside a module (for example, Projects › New Branch Office › Budget).

### Changed
- **Deleting a project is permanent** and removes its records, files, saved reports, and notifications. Deleted project codes are never reused.
- **Faster navigation**: permission settings load once per page, list pages load budget totals and latest progress in a few queries instead of several per project, duplicate hover prefetching was removed, and animations are shorter.
- Budget cards and the project tab bar fit on tablet and laptop widths without sideways scrolling.

## 2026-10-21

### Added
- **System Administrator** account (developer), separate from the CEO, with full access that the CEO cannot change.
- **Roles & Permissions** page: switch abilities on or off per role, with business-rule locks.
- **System Usage** page: sign-ins, failed sign-ins, last activity, and actions per user over a date range.

## 2026-10-20

### Added
- **Add Expenses** screen for Finance: several expenses at once, each a project, total amount, date, and optional reference.
- Full-screen **photo viewer** for progress photos and documents (zoom, swipe, keyboard).
- Admin/CEO can **delete projects** after typing the project code to confirm.

### Changed
- Only the Admin/CEO approves or rejects budget requests; Finance records expenses. Approving a request no longer creates an expense.
- Expense category and description are optional.
- Dark mode uses neutral graphite instead of blue.
- The in-page camera takes one photo per opening.
