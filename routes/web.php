<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetRequestController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesktopInstallerController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\ExpenseBatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressAssistController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemPermissionsController;
use App\Http\Controllers\SystemUsageController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Record ids in URLs are always numeric; anything else is a 404 before the database is asked.
Route::patterns(array_fill_keys(
    ['project', 'document', 'progress', 'budgetRequest', 'contractor', 'user', 'category', 'report'],
    '[0-9]+',
));

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/branding/logo', function () {
    $path = base_path('resources/logo/logo.png');

    abort_unless(file_exists($path), 404);

    return response()->file($path);
})->name('branding.logo');

// One-click Windows installer for the desktop app, offered on the login page.
Route::get('/desktop-app/download', DesktopInstallerController::class)
    ->middleware('throttle:10,1')
    ->name('desktop.installer');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/search', [SearchController::class, 'index'])
        ->middleware('throttle:search')
        ->name('search.index');

    Route::resource('projects', ProjectController::class);
    Route::get('/contractors/{contractor}', [ContractorController::class, 'show'])->name('contractors.show');

    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->name('notifications.recent');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    // Each report loads every visible project and may render a PDF or spreadsheet.
    Route::middleware('throttle:reports')->group(function () {
        Route::get('/reports/project-status', [ReportController::class, 'projectStatus'])
            ->name('reports.project-status');
        Route::get('/reports/budget', [ReportController::class, 'budget'])->name('reports.budget');
        Route::get('/reports/accomplishment', [ReportController::class, 'accomplishment'])->name('reports.accomplishment');
        Route::get('/reports/delayed-projects', [ReportController::class, 'delayedProjects'])->name('reports.delayed-projects');
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('/reports/contractor-history', [ReportController::class, 'contractorHistory'])->name('reports.contractor-history');
        Route::post('/reports/finance', [ReportController::class, 'storeFinanceReport'])->name('reports.finance.store');
        Route::get('/reports/finance/{report}/download', [ReportController::class, 'financeReportDownload'])->name('reports.finance.download');
    });

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index');

    Route::post('/projects/{project}/assignments', [ProjectController::class, 'assignPersonnel'])
        ->name('projects.assign');
    Route::get('/projects/{project}/contractors', [ContractorController::class, 'projectHistory'])
        ->name('projects.contractors.index');
    Route::post('/projects/{project}/contractors', [ContractorController::class, 'attachToProject'])
        ->name('projects.contractors.store');
    Route::post('/projects/{project}/quotations', [ContractorController::class, 'storeQuotation'])
        ->name('projects.quotations.store');
    Route::post('/projects/{project}/documents', [ProjectController::class, 'uploadDocument'])
        ->middleware('throttle:uploads')
        ->name('projects.documents.store');
    Route::post('/projects/{project}/progress', [ProjectController::class, 'storeProgress'])
        ->middleware('throttle:uploads')
        ->name('projects.progress.store');
    Route::put('/projects/{project}/progress/{progress}', [ProjectController::class, 'updateProgress'])
        ->middleware('throttle:uploads')
        ->name('projects.progress.update');
    Route::delete('/projects/{project}/progress/{progress}', [ProjectController::class, 'destroyProgress'])
        ->name('projects.progress.destroy');
    // Up to 10 AI drafts per user per minute keeps a stuck retry loop from running up API costs.
    Route::post('/projects/{project}/progress/assist', ProgressAssistController::class)
        ->middleware('throttle:10,1')
        ->name('projects.progress.assist');
    Route::get('/projects/{project}/documents/{document}/download', [ProjectController::class, 'downloadDocument'])
        ->name('projects.documents.download');
    Route::get('/projects/{project}/budget', [BudgetController::class, 'show'])
        ->name('projects.budget.show');
    Route::post('/projects/{project}/expenses', [BudgetController::class, 'storeExpense'])
        ->name('projects.expenses.store');
    Route::post('/projects/{project}/budget-requests', [BudgetRequestController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('projects.budget-requests.store');
    Route::patch('/projects/{project}/budget-requests/{budgetRequest}/approve', [BudgetRequestController::class, 'approve'])
        ->name('projects.budget-requests.approve');
    Route::patch('/projects/{project}/budget-requests/{budgetRequest}/reject', [BudgetRequestController::class, 'reject'])
        ->name('projects.budget-requests.reject');

    Route::get('/budget', [BudgetController::class, 'overview'])->name('budget.overview');
    Route::get('/budget/expenses', [BudgetController::class, 'expensesLog'])->name('budget.expenses');
    // Finance's quick entry: several expenses at once, each a project, date, amount, and optional reference.
    Route::get('/budget/expenses/new', [ExpenseBatchController::class, 'create'])->name('budget.expenses.batch');
    Route::post('/budget/expenses/batch', [ExpenseBatchController::class, 'store'])->name('budget.expenses.batch.store');
    Route::get('/budget/requests', [BudgetRequestController::class, 'index'])->name('budget.requests');

    Route::resource('users', UsersController::class)->except(['show', 'edit']);
    Route::patch('/users/{user}/toggle-status', [UsersController::class, 'toggleStatus'])->name('users.toggle-status');

    // System Administrator (developer account) only.
    Route::middleware('can:manage-system')->prefix('system')->name('system.')->group(function () {
        Route::get('/permissions', [SystemPermissionsController::class, 'edit'])->name('permissions');
        Route::put('/permissions', [SystemPermissionsController::class, 'update'])->name('permissions.update');
        Route::get('/usage', [SystemUsageController::class, 'index'])->name('usage');
        Route::get('/usage/{user}', [SystemUsageController::class, 'show'])->name('usage.user');
    });

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/categories', [ProjectCategoryController::class, 'store'])->name('settings.categories.store');
    Route::put('/settings/categories/{category}', [ProjectCategoryController::class, 'update'])->name('settings.categories.update');
    Route::delete('/settings/categories/{category}', [ProjectCategoryController::class, 'destroy'])->name('settings.categories.destroy');
});

require __DIR__.'/auth.php';
