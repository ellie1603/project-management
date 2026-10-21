/**
 * Alpine component behind Finance's "Add Expenses" screen.
 *
 * Each expense is just a project, a date, the total amount, and an optional
 * reference number. New rows copy the project and date of the row above, and
 * Enter moves to the next row. Unsaved rows are kept in this browser, so
 * leaving the page never loses typed work.
 */

const FIELDS = ['project_id', 'expense_date', 'amount', 'reference_number'];

let nextKey = 1;

const peso = (value) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default (config) => ({
    projects: config.projects,
    today: config.today,
    maxRows: config.maxRows,
    defaultProjectId: config.defaultProjectId ? String(config.defaultProjectId) : '',

    rows: [],
    saving: false,
    formError: '',
    restored: false,

    init() {
        this.restored = this.restoreDraft();

        if (this.rows.length === 0) {
            this.rows = [this.newRow()];
        }

        this.$watch('rows', () => this.saveDraft());
    },

    newRow(from = null) {
        return {
            key: nextKey++,
            project_id: from?.project_id || this.defaultProjectId,
            expense_date: from?.expense_date || this.today,
            amount: '',
            reference_number: '',
            errors: {},
        };
    },

    addRow() {
        if (this.rows.length >= this.maxRows) {
            this.formError = `Save these first. Up to ${this.maxRows} expenses can be saved at a time.`;
            return;
        }

        const row = this.newRow(this.rows[this.rows.length - 1]);
        this.rows.push(row);
        this.focusRow(row);
    },

    removeRow(row) {
        this.rows = this.rows.filter((r) => r !== row);

        if (this.rows.length === 0) {
            this.rows = [this.newRow()];
        }
    },

    // Enter moves to the next row, adding one at the end, instead of submitting.
    nextRow(row) {
        const index = this.rows.indexOf(row);

        if (index === this.rows.length - 1) {
            this.addRow();
        } else {
            this.focusRow(this.rows[index + 1]);
        }
    },

    focusRow(row) {
        this.$nextTick(() => document.querySelector(`[data-row="${row.key}"] [data-field="amount"]`)?.focus());
    },

    edited(row, field) {
        delete row.errors[field];
    },

    isBlank(row) {
        return String(row.amount ?? '').trim() === '' && String(row.reference_number ?? '').trim() === '';
    },

    project(id) {
        return this.projects.find((p) => String(p.id) === String(id)) ?? null;
    },

    get filledRows() {
        return this.rows.filter((row) => !this.isBlank(row));
    },

    get grandTotal() {
        return this.filledRows.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
    },

    // Per-project totals with the remaining budget after this batch.
    get projectTotals() {
        const totals = new Map();

        this.filledRows.forEach((row) => {
            const project = this.project(row.project_id);

            if (!project) {
                return;
            }

            const entry = totals.get(project.id) ?? { project, total: 0 };
            entry.total += Number(row.amount) || 0;
            totals.set(project.id, entry);
        });

        return [...totals.values()].map((entry) => ({ ...entry, after: entry.project.remaining - entry.total }));
    },

    peso,

    async save() {
        this.formError = '';
        const rows = this.filledRows;

        if (rows.length === 0) {
            this.formError = 'Enter at least one amount.';
            return;
        }

        this.rows.forEach((row) => (row.errors = {}));
        this.saving = true;

        try {
            const response = await fetch(config.saveUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({
                    rows: rows.map((row) => Object.fromEntries(FIELDS.map((field) => [field, String(row[field] ?? '').trim() || null]))),
                }),
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                this.clearDraft();
                window.Turbo ? window.Turbo.visit(data.redirect) : (window.location.href = data.redirect);
                return;
            }

            if (response.status === 422 && data.errors) {
                this.showErrors(rows, data.errors);
                return;
            }

            this.formError = response.status === 419
                ? 'Your session expired. Reload the page; your entries are kept.'
                : 'The expenses could not be saved. Try again.';
        } catch {
            this.formError = 'The expenses could not be saved. Check the connection and try again.';
        } finally {
            this.saving = false;
        }
    },

    showErrors(sentRows, errors) {
        let firstRow = null;

        Object.entries(errors).forEach(([key, messages]) => {
            const match = key.match(/^rows\.(\d+)\.(\w+)$/);

            if (!match) {
                this.formError = messages[0];
                return;
            }

            const row = sentRows[Number(match[1])];

            if (row) {
                row.errors = { ...row.errors, [match[2]]: messages[0] };
                firstRow ??= row;
            }
        });

        const count = sentRows.filter((row) => Object.keys(row.errors).length > 0).length;

        if (count > 0) {
            this.formError = `${count} ${count === 1 ? 'expense needs' : 'expenses need'} fixing. Nothing was saved yet.`;
        }

        if (firstRow) {
            this.$nextTick(() => document.querySelector(`[data-row="${firstRow.key}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        }
    },

    /* ----- Draft kept in this browser ----- */

    saveDraft() {
        try {
            const draft = this.filledRows.map((row) => Object.fromEntries(FIELDS.map((field) => [field, row[field]])));

            draft.length > 0
                ? localStorage.setItem(config.draftKey, JSON.stringify(draft))
                : localStorage.removeItem(config.draftKey);
        } catch {
            // Storage can be unavailable (private mode); the draft is only a convenience.
        }
    },

    restoreDraft() {
        try {
            const draft = JSON.parse(localStorage.getItem(config.draftKey) ?? '[]');

            if (!Array.isArray(draft) || draft.length === 0) {
                return false;
            }

            this.rows = draft.map((saved) => ({
                ...this.newRow(),
                ...Object.fromEntries(FIELDS.map((field) => [field, saved[field] ?? ''])),
            }));

            return true;
        } catch {
            return false;
        }
    },

    clearDraft() {
        try {
            localStorage.removeItem(config.draftKey);
        } catch {
            // Nothing to clear.
        }
    },

    startOver() {
        this.rows = [this.newRow()];
        this.restored = false;
        this.formError = '';
        this.clearDraft();
    },
});
