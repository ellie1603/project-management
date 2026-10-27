# Changelog

Notable changes to the BMPC Project Management System. Newest first.

## 2026-10-27

### Changed
- **Users and Audit Logs are System Administrator pages.** The CEO no longer sees them in the sidebar, and opening their addresses directly shows Access Denied. Creating accounts, resetting passwords, and deactivating users are done by the System Administrator; the CEO keeps Settings and everything project-related. Finance can still be given Audit Logs on Roles & Permissions.
- **System Administrator and CEO are separate account types.** On Users, the Role list is System Administrator, CEO, Project Personnel, and Finance & Accounting, and every badge, filter, and profile shows "CEO" or "System Administrator" instead of "Admin / CEO". Both are still the admin role underneath, so the CEO's abilities are unchanged. A System Administrator can't change their own account to another type.
- **Finance no longer sees budget requests**: no Budget Requests page or sidebar item, no Pending Requests card on the Finance dashboard or Budget & Finance, and no requests on a project's Budget tab. Requests stay between contractor personnel and the CEO; Finance records the actual payments as expenses. A new "See budget requests" permission (on for the CEO and personnel) lets the System Administrator turn it back on for Finance on Roles & Permissions.
- PDF reports no longer have signature lines ("Prepared by", "Noted by", "Approved by"); they end with the report itself.
- Servers seeded before the System Administrator existed get that account flagged automatically on the next deploy (the `admin@bmpc.test` account, or the only admin account named "System Administrator").

## 2026-10-26

### Added
- **Weighted, step-by-step progress.** Each construction stage now has a share of the whole project (new projects: Construction Started 5%, Foundation 25%, Building 35%, Installation & Finishing 25%, Inspection & Completion 10%), and personnel record how far along the stage is (0-100%, in 5% steps) instead of only "still working" or "finished". Overall completion = each stage's progress times its share, so every update on a long project moves the number. A stage can't be set lower than what's already recorded (delete the wrong update instead), and completion only reaches 100% when every stage is finished.
- **Planned vs actual.** The Progress tab shows where completion should be today according to the schedule and how many points the project is ahead or behind. Projects 10 or more points behind appear under Projects Requiring Attention, and on the personnel dashboard as a required action.
- **Stage Shares** (admin, Progress tab) to set each stage's share per project; they must add up to 100%. Existing projects keep equal shares until changed, so their completion doesn't jump.
- **Installable app** on phones and computers, from one link for every device: `/install` (also linked on the sign-in page). Android and desktop Chrome/Edge get an Install button, iPhone and iPad get Add to Home Screen steps, and Windows keeps its installer download. It's the same system in its own window, and using it in the browser still works. Losing the connection shows a clear offline page; no project data is stored on the device.
- **My Account** for every user (sidebar item, the account card at the bottom of the sidebar, and the top-right menu): edit name and email, change password, and choose notifications.
- **Admin password reset** on Users: the admin gets a one-time code (valid 24 hours) to give the person, who signs in with their email and the code, then must set a new password before doing anything else. The old password and open sessions stop working immediately; the code is never stored or logged in readable form.

### Changed (My Account)
- **My Account** redesigned to match the rest of the system: an account summary (role, position, company, member since, previous sign-in) beside cards for account details, password, and notifications, with the usual success messages.
- Users can no longer delete their own account; removing accounts is done by the administrator on Users, since accounts carry project history.

### Fixed
- Dashboard figures could freeze at a half-counted amount right after signing in (for example ₱279,855.23 instead of ₱2,500,000.00), because the count-up animation could start twice. Each figure now animates once and always ends on the real amount.

### Changed
- The expense reference can be a reference number or a reference person. Numbers (anything with a digit) are still recorded only once per project; names can repeat.
- **Add Expenses opens as a dialog** on the Expenses page and on a project's Budget tab (with that project already chosen), instead of a separate page. After saving, you stay on the same page. The old Add Expenses address now opens the dialog.

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
