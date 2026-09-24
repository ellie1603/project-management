<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetRequestController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/branding/logo', function () {
    $path = base_path('resources/logo/logo.png');

    abort_unless(file_exists($path), 404);

    return response()->file($path);
})->name('branding.logo');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    Route::resource('projects', ProjectController::class);
    Route::get('/contractors/{contractor}', [ContractorController::class, 'show'])->name('contractors.show');

    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->name('notifications.recent');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/project-status', [ReportController::class, 'projectStatus'])
        ->name('reports.project-status');
    Route::get('/reports/budget', [ReportController::class, 'budget'])->name('reports.budget');
    Route::get('/reports/accomplishment', [ReportController::class, 'accomplishment'])->name('reports.accomplishment');
    Route::get('/reports/delayed-projects', [ReportController::class, 'delayedProjects'])->name('reports.delayed-projects');
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
    Route::get('/reports/contractor-history', [ReportController::class, 'contractorHistory'])->name('reports.contractor-history');
    Route::post('/reports/finance', [ReportController::class, 'storeFinanceReport'])->name('reports.finance.store');
    Route::get('/reports/finance/{report}/download', [ReportController::class, 'financeReportDownload'])->name('reports.finance.download');

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
        ->name('projects.documents.store');
    Route::post('/projects/{project}/progress', [ProjectController::class, 'storeProgress'])
        ->name('projects.progress.store');
    Route::get('/projects/{project}/documents/{document}/download', [ProjectController::class, 'downloadDocument'])
        ->name('projects.documents.download');
    Route::get('/projects/{project}/budget', [BudgetController::class, 'show'])
        ->name('projects.budget.show');
    Route::post('/projects/{project}/expenses', [BudgetController::class, 'storeExpense'])
        ->name('projects.expenses.store');
    Route::post('/projects/{project}/budget-requests', [BudgetRequestController::class, 'store'])
        ->name('projects.budget-requests.store');
    Route::patch('/projects/{project}/budget-requests/{budgetRequest}/approve', [BudgetRequestController::class, 'approve'])
        ->name('projects.budget-requests.approve');
    Route::patch('/projects/{project}/budget-requests/{budgetRequest}/reject', [BudgetRequestController::class, 'reject'])
        ->name('projects.budget-requests.reject');

    Route::get('/budget', [BudgetController::class, 'overview'])->name('budget.overview');
    Route::get('/budget/expenses', [BudgetController::class, 'expensesLog'])->name('budget.expenses');
    Route::get('/budget/requests', [BudgetRequestController::class, 'index'])->name('budget.requests');

    Route::resource('users', UsersController::class)->except(['destroy', 'show', 'edit']);
    Route::patch('/users/{user}/toggle-status', [UsersController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/categories', [ProjectCategoryController::class, 'store'])->name('settings.categories.store');
    Route::put('/settings/categories/{category}', [ProjectCategoryController::class, 'update'])->name('settings.categories.update');
    Route::delete('/settings/categories/{category}', [ProjectCategoryController::class, 'destroy'])->name('settings.categories.destroy');
});

require __DIR__.'/auth.php';
