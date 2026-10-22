<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * What each of the three roles may do, set by the System Administrator on
 * the Roles & Permissions page. Every permission lists the roles it can
 * ever be given to: the rest are locked by BMPC's business rules (for
 * example, only the Admin/CEO approves budget requests). Row-level rules,
 * such as personnel seeing only their assigned projects, stay in place
 * regardless.
 *
 * The System Administrator account always has every permission.
 */
class Permissions
{
    public const ROLES = [
        'admin' => 'Admin / CEO',
        'finance_accounting' => 'Finance & Accounting',
        'project_personnel' => 'Project Personnel',
    ];

    private const ADMIN = 'admin';

    private const FINANCE = 'finance_accounting';

    private const PERSONNEL = 'project_personnel';

    private const CACHE_KEY = 'role-permissions';

    /**
     * Grouped catalog. "roles" = roles it may be granted to; "default" = roles that have it initially.
     *
     * @return array<string, array<string, array{label: string, description: string, roles: array<int, string>, default: array<int, string>}>>
     */
    public static function catalog(): array
    {
        return [
            'Projects' => [
                'projects.view_all' => [
                    'label' => 'See every project',
                    'description' => 'Without this, a user only sees projects they are assigned to.',
                    'roles' => [self::ADMIN, self::FINANCE],
                    'default' => [self::ADMIN, self::FINANCE],
                ],
                'projects.register' => [
                    'label' => 'Register projects',
                    'description' => 'Enter newly approved projects into the system.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'projects.edit' => [
                    'label' => 'Edit project information',
                    'description' => 'Change project details, timeline, and status.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'projects.delete' => [
                    'label' => 'Delete projects',
                    'description' => 'Permanently delete a project and everything in it.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'projects.assign' => [
                    'label' => 'Assign personnel',
                    'description' => 'Assign project personnel to projects.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'contractors.manage' => [
                    'label' => 'Manage contractors and quotations',
                    'description' => 'Link contractors to projects and record quotations.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
            ],
            'Progress & Documents' => [
                'progress.update' => [
                    'label' => 'Submit progress updates',
                    'description' => 'Report progress, photos, and issues on assigned projects.',
                    'roles' => [self::PERSONNEL],
                    'default' => [self::PERSONNEL],
                ],
                'documents.upload' => [
                    'label' => 'Upload project documents',
                    'description' => 'Upload plans, reports, photos, and other files to a project.',
                    'roles' => [self::ADMIN, self::FINANCE, self::PERSONNEL],
                    'default' => [self::ADMIN, self::PERSONNEL],
                ],
                'documents.design' => [
                    'label' => 'Upload approved designs',
                    'description' => 'Add new versions of the official approved design.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'documents.library' => [
                    'label' => 'Browse the Documents page',
                    'description' => 'See documents from every project in one place.',
                    'roles' => [self::ADMIN, self::FINANCE],
                    'default' => [self::ADMIN],
                ],
            ],
            'Budget & Finance' => [
                'finance.view' => [
                    'label' => 'View budget and expenses',
                    'description' => 'Open Budget & Finance, the expense list, and budget requests.',
                    'roles' => [self::ADMIN, self::FINANCE],
                    'default' => [self::ADMIN, self::FINANCE],
                ],
                'expenses.record' => [
                    'label' => 'Record expenses',
                    'description' => 'Enter project expenses.',
                    'roles' => [self::ADMIN, self::FINANCE],
                    'default' => [self::FINANCE],
                ],
                'budget_requests.submit' => [
                    'label' => 'Submit budget requests',
                    'description' => 'Ask the Admin/CEO for budget on an assigned project.',
                    'roles' => [self::PERSONNEL],
                    'default' => [self::PERSONNEL],
                ],
                'budget_requests.review' => [
                    'label' => 'Approve or reject budget requests',
                    'description' => 'Decide on budget requests from project personnel.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
            ],
            'Reports & Oversight' => [
                'reports.view' => [
                    'label' => 'Open reports',
                    'description' => 'Generate and download PDF and Excel reports. Personnel only report on assigned projects.',
                    'roles' => [self::ADMIN, self::FINANCE, self::PERSONNEL],
                    'default' => [self::ADMIN, self::FINANCE, self::PERSONNEL],
                ],
                'audit_logs.view' => [
                    'label' => 'View audit logs',
                    'description' => 'See the full history of actions taken in the system.',
                    'roles' => [self::ADMIN, self::FINANCE],
                    'default' => [self::ADMIN],
                ],
            ],
            'Administration' => [
                'users.manage' => [
                    'label' => 'Manage users',
                    'description' => 'Create, edit, deactivate, and delete user accounts.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
                'settings.manage' => [
                    'label' => 'Manage settings',
                    'description' => 'Change system settings and project categories.',
                    'roles' => [self::ADMIN],
                    'default' => [self::ADMIN],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, roles: array<int, string>, default: array<int, string>}>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::catalog()));
    }

    public static function exists(string $permission): bool
    {
        return array_key_exists($permission, self::all());
    }

    public static function label(string $permission): string
    {
        return self::all()[$permission]['label'] ?? $permission;
    }

    public static function grantable(string $role, string $permission): bool
    {
        return in_array($role, self::all()[$permission]['roles'] ?? [], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaults(): array
    {
        $defaults = array_fill_keys(array_keys(self::ROLES), []);

        foreach (self::all() as $permission => $definition) {
            foreach ($definition['default'] as $role) {
                $defaults[$role][] = $permission;
            }
        }

        return $defaults;
    }

    public static function roleHas(?string $role, string $permission): bool
    {
        if ($role === null) {
            return false;
        }

        if (! self::exists($permission)) {
            throw new InvalidArgumentException("Unknown permission [{$permission}].");
        }

        return in_array($permission, self::grantedMap()[$role] ?? [], true);
    }

    /**
     * Current grants per role, as saved by the System Administrator.
     *
     * @return array<string, array<int, string>>
     */
    public static function grantedMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $map = array_fill_keys(array_keys(self::ROLES), []);

            DB::table('role_permissions')->orderBy('permission')->get(['role', 'permission'])
                ->each(function (object $row) use (&$map): void {
                    if (isset($map[$row->role]) && self::grantable($row->role, $row->permission)) {
                        $map[$row->role][] = $row->permission;
                    }
                });

            return $map;
        });
    }

    /**
     * Replaces every role's grants. Throws for a permission a role may not have.
     *
     * @param  array<string, array<int, string>>  $matrix
     * @return array<int, array{role: string, permission: string, granted: bool}> what changed
     */
    public static function save(array $matrix): array
    {
        foreach ($matrix as $role => $permissions) {
            foreach ($permissions as $permission) {
                if (! self::grantable($role, $permission)) {
                    throw new InvalidArgumentException(self::label($permission).' cannot be given to '.(self::ROLES[$role] ?? $role).'.');
                }
            }
        }

        $before = self::grantedMap();
        $changes = [];

        DB::transaction(function () use ($matrix, $before, &$changes): void {
            foreach (array_keys(self::ROLES) as $role) {
                $wanted = array_values(array_unique($matrix[$role] ?? []));
                $had = $before[$role] ?? [];

                foreach (array_diff($wanted, $had) as $permission) {
                    DB::table('role_permissions')->insert(['role' => $role, 'permission' => $permission, 'created_at' => now(), 'updated_at' => now()]);
                    $changes[] = ['role' => $role, 'permission' => $permission, 'granted' => true];
                }

                $removed = array_diff($had, $wanted);
                if ($removed !== []) {
                    DB::table('role_permissions')->where('role', $role)->whereIn('permission', $removed)->delete();

                    foreach ($removed as $permission) {
                        $changes[] = ['role' => $role, 'permission' => $permission, 'granted' => false];
                    }
                }
            }
        });

        self::forget();

        return $changes;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
