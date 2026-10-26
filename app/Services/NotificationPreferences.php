<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\BudgetApproachingLimitNotification;
use App\Notifications\BudgetExceededNotification;
use App\Notifications\BudgetRequestApprovedNotification;
use App\Notifications\BudgetRequestRejectedNotification;
use App\Notifications\BudgetRequestSubmittedNotification;
use App\Notifications\NewDocumentUploadedNotification;
use App\Notifications\ProjectApproachingDeadlineNotification;
use App\Notifications\ProjectAssignmentNotification;
use App\Notifications\ProjectCompletedNotification;
use App\Notifications\ProjectDelayedNotification;
use App\Notifications\ProjectProgressUpdatedNotification;

/**
 * Which in-system notifications each user wants, chosen in their Profile.
 * Users store only the types they turned off; "required" types are always
 * delivered (BMPC's rules say budget overruns must reach Admin and Finance,
 * and personnel must learn about new assignments).
 */
class NotificationPreferences
{
    private const ADMIN = 'admin';

    private const FINANCE = 'finance_accounting';

    private const PERSONNEL = 'project_personnel';

    /**
     * @return array<string, array{label: string, description: string, classes: array<int, class-string>, roles: array<int, string>, required: bool}>
     */
    public static function types(): array
    {
        return [
            'assignment' => [
                'label' => 'Project assigned to you',
                'description' => 'When the Admin/CEO assigns you to a project.',
                'classes' => [ProjectAssignmentNotification::class],
                'roles' => [self::PERSONNEL],
                'required' => true,
            ],
            'progress_updated' => [
                'label' => 'Project progress updated',
                'description' => 'When personnel submit a progress update.',
                'classes' => [ProjectProgressUpdatedNotification::class],
                'roles' => [self::ADMIN, self::FINANCE, self::PERSONNEL],
                'required' => false,
            ],
            'document_uploaded' => [
                'label' => 'New document uploaded',
                'description' => 'When a document is added to one of your projects.',
                'classes' => [NewDocumentUploadedNotification::class],
                'roles' => [self::ADMIN, self::PERSONNEL],
                'required' => false,
            ],
            'project_completed' => [
                'label' => 'Project completed',
                'description' => 'When a project reaches 100% completion.',
                'classes' => [ProjectCompletedNotification::class],
                'roles' => [self::ADMIN, self::FINANCE, self::PERSONNEL],
                'required' => false,
            ],
            'deadline_approaching' => [
                'label' => 'Deadline approaching',
                'description' => 'Daily reminder when a project nears its target completion date.',
                'classes' => [ProjectApproachingDeadlineNotification::class],
                'roles' => [self::ADMIN, self::PERSONNEL],
                'required' => false,
            ],
            'project_delayed' => [
                'label' => 'Project delayed',
                'description' => 'When a project passes its target completion date unfinished.',
                'classes' => [ProjectDelayedNotification::class],
                'roles' => [self::ADMIN, self::PERSONNEL],
                'required' => false,
            ],
            'budget_approaching' => [
                'label' => 'Budget approaching its limit',
                'description' => 'When a project crosses the budget warning threshold.',
                'classes' => [BudgetApproachingLimitNotification::class],
                'roles' => [self::ADMIN, self::FINANCE],
                'required' => false,
            ],
            'budget_exceeded' => [
                'label' => 'Budget exceeded',
                'description' => 'When a project spends more than its budget.',
                'classes' => [BudgetExceededNotification::class],
                'roles' => [self::ADMIN, self::FINANCE],
                'required' => true,
            ],
            'request_submitted' => [
                'label' => 'New budget request',
                'description' => 'When a contractor submits a budget request for your decision.',
                'classes' => [BudgetRequestSubmittedNotification::class],
                'roles' => [self::ADMIN],
                'required' => false,
            ],
            'request_decided' => [
                'label' => 'Decision on your budget request',
                'description' => 'When the Admin/CEO approves or rejects your request.',
                'classes' => [BudgetRequestApprovedNotification::class, BudgetRequestRejectedNotification::class],
                'roles' => [self::PERSONNEL],
                'required' => false,
            ],
        ];
    }

    /**
     * The types that can reach this user's role.
     *
     * @return array<string, array{label: string, description: string, classes: array<int, class-string>, roles: array<int, string>, required: bool}>
     */
    public static function forUser(User $user): array
    {
        return array_filter(self::types(), fn (array $type): bool => in_array($user->role, $type['roles'], true));
    }

    public static function wants(User $user, string $notificationClass): bool
    {
        foreach (self::types() as $key => $type) {
            if (in_array($notificationClass, $type['classes'], true)) {
                return $type['required'] || ! in_array($key, $user->notification_preferences['muted'] ?? [], true);
            }
        }

        return true;
    }

    /**
     * Saves the user's choices: anything offered to their role and not
     * ticked is muted, except required types.
     *
     * @param  array<int, string>  $enabled
     */
    public static function save(User $user, array $enabled): void
    {
        $muted = collect(self::forUser($user))
            ->reject(fn (array $type, string $key): bool => $type['required'] || in_array($key, $enabled, true))
            ->keys()
            ->values()
            ->all();

        $user->forceFill(['notification_preferences' => ['muted' => $muted]])->save();
    }
}
