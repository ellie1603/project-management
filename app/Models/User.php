<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'position_type', 'is_active', 'contractor_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'position_type',
        'is_active',
        'contractor_id',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProjectPersonnel(): bool
    {
        return $this->role === 'project_personnel';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance_accounting';
    }

    /**
     * The developer account that maintains the system: an Admin/CEO-role user
     * flagged as System Administrator. It always has every permission and is
     * the only account that manages roles & permissions and views system usage.
     */
    public function isSystemAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_system_admin;
    }

    /**
     * Whether this user's role has the given permission (see App\Services\Permissions).
     */
    public function hasPermission(string $permission): bool
    {
        return $this->isSystemAdmin() || Permissions::roleHas($this->role, $permission);
    }

    public function roleLabel(): string
    {
        return $this->isSystemAdmin() ? 'System Administrator' : (Permissions::ROLES[$this->role] ?? Str::headline((string) $this->role));
    }

    public function signIns(): HasMany
    {
        return $this->hasMany(UserSignIn::class);
    }

    public function projectsCreated(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_assignments');
    }

    /**
     * Kinds of project records this user authored. A plain delete would break
     * these (foreign keys) or lose who did the work, so they need a force delete.
     *
     * @return array<int, string>
     */
    public function recordedWork(): array
    {
        $checks = [
            'registered projects' => DB::table('projects')->where('created_by', $this->id),
            'progress updates' => DB::table('project_progress')->where('user_id', $this->id),
            'uploaded documents' => DB::table('project_documents')->where('uploaded_by', $this->id),
            'project expenses' => DB::table('project_expenses')->where('created_by', $this->id),
            'budget requests' => DB::table('budget_requests')->where('requested_by', $this->id),
            'finance reports' => DB::table('finance_reports')->where('generated_by', $this->id),
        ];

        return array_keys(array_filter($checks, fn ($query): bool => $query->exists()));
    }

    /**
     * Delete the account. With no recorded work the row is removed outright;
     * otherwise it is soft-deleted so history keeps its author, while the
     * login, email, assignments, and notifications go away.
     */
    public function removeAccount(): void
    {
        if ($this->recordedWork() === []) {
            $this->forceDelete();

            return;
        }

        DB::transaction(function (): void {
            $this->projectAssignments()->delete();
            $this->notifications()->delete();
            $this->forceFill([
                'email' => "deleted-{$this->id}-".now()->timestamp.'@deleted.invalid',
                'is_active' => false,
                'remember_token' => null,
            ])->save();
            $this->delete();
        });
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'position_type' => 'string',
            'is_active' => 'boolean',
            'is_system_admin' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }
}
