<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('contractor')
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return $this->respond($request, 'users.index', 'users.partials.results', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $this->validatedUser($request, requirePassword: true);
        $contractorData = $this->validatedContractorProfile($request, $validated['position_type'] ?? null);

        $user = DB::transaction(function () use ($validated, $contractorData): User {
            $user = User::create([
                ...$validated,
                'password' => Hash::make($validated['password']),
            ]);

            if ($contractorData !== null) {
                $contractor = Contractor::create($contractorData);
                $user->update(['contractor_id' => $contractor->id]);
            }

            return $user;
        });

        app(AuditLogger::class)->record(
            $request,
            'created',
            'users',
            $user->id,
            "Created user account for {$user->name}.",
            null,
            $user->makeHidden('password')->toArray(),
        );

        return redirect()->route('users.index')->with('status', 'User account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $this->validatedUser($request, requirePassword: false, ignoreUserId: $user->id);
        $contractorData = $this->validatedContractorProfile($request, $validated['position_type'] ?? null);
        $oldValues = $user->makeHidden('password')->toArray();

        DB::transaction(function () use ($user, $validated, $contractorData): void {
            $user->fill($validated);

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if ($contractorData !== null) {
                if ($user->contractor_id) {
                    $user->contractor->update($contractorData);
                } else {
                    $contractor = Contractor::create($contractorData);
                    $user->contractor_id = $contractor->id;
                }
            }

            $user->save();
        });

        app(AuditLogger::class)->record(
            $request,
            'updated',
            'users',
            $user->id,
            "Updated user account for {$user->name}.",
            $oldValues,
            $user->fresh()->makeHidden('password')->toArray(),
        );

        return redirect()->route('users.index')->with('status', 'User account updated successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleStatus', $user);

        $oldValues = $user->toArray();
        $user->update(['is_active' => ! $user->is_active]);

        app(AuditLogger::class)->record(
            $request,
            $user->is_active ? 'activated' : 'deactivated',
            'users',
            $user->id,
            ($user->is_active ? 'Activated' : 'Deactivated')." user account for {$user->name}.",
            $oldValues,
            $user->fresh()->toArray(),
        );

        return redirect()->route('users.index')->with('status', 'User status updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUser(Request $request, bool $requirePassword, ?int $ignoreUserId = null): array
    {
        $emailRule = Rule::unique('users', 'email')->ignore($ignoreUserId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $emailRule],
            'password' => [$requirePassword ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'project_personnel', 'finance_accounting'])],
            'position_type' => [
                Rule::requiredIf(fn () => $request->input('role') === 'project_personnel'),
                'nullable',
                Rule::in(ProjectAssignment::POSITION_TYPES),
            ],
        ]);

        if ($validated['role'] !== 'project_personnel') {
            $validated['position_type'] = null;
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }

    /**
     * Only present when the account represents a contractor/provider —
     * validates and returns the linked Contractor company-profile fields,
     * or null when position_type isn't Contractor.
     *
     * @return array<string, mixed>|null
     */
    private function validatedContractorProfile(Request $request, ?string $positionType): ?array
    {
        if ($positionType !== 'Contractor') {
            return null;
        }

        $validated = $request->validate([
            'contractor_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contractor_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'registration_information' => ['nullable', 'string'],
            'contractor_status' => ['required', Rule::in(['active', 'inactive'])],
        ], [], [
            'contractor_name' => 'contractor name',
            'contractor_email' => 'business email',
            'contractor_status' => 'contractor status',
        ]);

        return [
            'name' => $validated['contractor_name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['contractor_email'] ?? null,
            'address' => $validated['address'] ?? null,
            'registration_information' => $validated['registration_information'] ?? null,
            'status' => $validated['contractor_status'],
        ];
    }
}
