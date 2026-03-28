<?php

namespace App\Livewire\Pages\Admin\Users;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.layout-main')]
class IndexPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $formName = '';

    public string $formEmail = '';

    public string $formPassword = '';

    public string $formPasswordConfirmation = '';

    public ?int $formOrganizationId = null;

    /** @var array<int, string> */
    public array $formRoles = [];

    /**
     * Pesan toast di modal saat pilihan role dinormalisasi (eksklusif vs role lain).
     */
    public ?string $roleExclusiveToast = null;

    /**
     * Pesan di modal saat simpan (ubah) tanpa perubahan data.
     */
    public ?string $formNoChangesToast = null;

    /**
     * Role yang tidak boleh digabung dengan role lain (hanya satu role total untuk pengguna).
     *
     * @return list<string>
     */
    private static function exclusiveStandaloneRoleNames(): array
    {
        return ['admin', 'koordinator', 'pimpinan'];
    }

    /**
     * Jika salah satu role eksklusif dipilih, daftar role disederhanakan menjadi hanya role itu
     * (menjatuhkan role lain). Jika lebih dari satu eksklusif tercentang, dipilih satu menurut prioritas.
     *
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function normalizeRolesForExclusiveRules(array $roles): array
    {
        $roles = array_values(array_unique($roles));
        $exclusive = self::exclusiveStandaloneRoleNames();
        $picked = array_values(array_intersect($roles, $exclusive));

        if ($picked === []) {
            return $roles;
        }

        if (count($picked) > 1) {
            foreach ($exclusive as $name) {
                if (in_array($name, $picked, true)) {
                    return [$name];
                }
            }
        }

        return [$picked[0]];
    }

    public function updatedFormRoles(): void
    {
        $current = array_values(array_unique(array_map('strval', is_array($this->formRoles) ? $this->formRoles : [])));
        $normalized = $this->normalizeRolesForExclusiveRules($current);

        $currentSorted = $current;
        $normalizedSorted = $normalized;
        sort($currentSorted);
        sort($normalizedSorted);

        if ($currentSorted === $normalizedSorted) {
            if ($this->formRoles !== $normalized) {
                $this->formRoles = $normalized;
            }
            $this->roleExclusiveToast = null;

            return;
        }

        $this->formRoles = $normalized;

        $exclusive = self::exclusiveStandaloneRoleNames();
        $picked = array_values(array_intersect($current, $exclusive));
        $violatesExclusive = count($picked) > 1
            || (count($picked) === 1 && count($current) > 1);

        $this->roleExclusiveToast = $violatesExclusive
            ? (count($picked) > 1
                ? 'Hanya satu role di antara Admin, Koordinator, atau Pimpinan yang dapat dipilih. Pilihan telah disesuaikan.'
                : 'Role Admin, Koordinator, atau Pimpinan tidak dapat digabung dengan role lain. Pilihan telah disesuaikan.')
            : null;
    }

    public function dismissRoleExclusiveToast(): void
    {
        $this->roleExclusiveToast = null;
    }

    public function dismissFormNoChangesToast(): void
    {
        $this->formNoChangesToast = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        $allowed = ['name', 'email', 'created_at'];
        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->can('user.create'), 403);

        $this->resetValidation();
        $this->editingId = null;
        $this->formName = '';
        $this->formEmail = '';
        $this->formPassword = '';
        $this->formPasswordConfirmation = '';
        $this->formOrganizationId = Organization::query()->orderBy('name')->value('id');
        $this->formRoles = [];
        $this->roleExclusiveToast = null;
        $this->formNoChangesToast = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $userId): void
    {
        abort_unless(auth()->user()?->can('user.update'), 403);

        $user = User::query()->with('roles')->findOrFail($userId);

        $this->resetValidation();
        $this->editingId = $user->id;
        $this->formName = $user->name;
        $this->formEmail = $user->email;
        $this->formPassword = '';
        $this->formPasswordConfirmation = '';
        $this->formOrganizationId = $user->organization_id;
        $this->formRoles = $this->normalizeRolesForExclusiveRules($user->roles->pluck('name')->all());
        $this->roleExclusiveToast = null;
        $this->formNoChangesToast = null;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->roleExclusiveToast = null;
        $this->formNoChangesToast = null;
    }

    public function saveUser(): void
    {
        if ($this->editingId) {
            abort_unless(auth()->user()?->can('user.update'), 403);
        } else {
            abort_unless(auth()->user()?->can('user.create'), 403);
        }

        $rules = [
            'formName' => ['required', 'string', 'max:255'],
            'formEmail' => [
                'required',
                'email',
                'max:255',
                $this->editingId
                    ? Rule::unique('users', 'email')->ignore($this->editingId)
                    : Rule::unique('users', 'email'),
            ],
            'formOrganizationId' => ['required', 'exists:organizations,id'],
            'formRoles' => [
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $roles = array_values(array_unique(array_map('strval', is_array($value) ? $value : [])));
                    $exclusive = self::exclusiveStandaloneRoleNames();
                    $picked = array_values(array_intersect($roles, $exclusive));

                    if (count($picked) > 1) {
                        $fail('Hanya satu role di antara Admin, Koordinator, atau Pimpinan yang boleh dipilih.');
                    }

                    if (count($picked) === 1 && count($roles) > 1) {
                        $fail('Role Admin, Koordinator, atau Pimpinan tidak dapat digabung dengan role lain.');
                    }
                },
            ],
            'formRoles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];

        if ($this->editingId) {
            $rules['formPassword'] = ['nullable', 'string', 'min:8', 'same:formPasswordConfirmation'];
            $rules['formPasswordConfirmation'] = ['nullable', 'string', 'min:8'];
        } else {
            $rules['formPassword'] = ['required', 'string', 'min:8', 'same:formPasswordConfirmation'];
            $rules['formPasswordConfirmation'] = ['required', 'string', 'min:8'];
        }

        $validated = $this->validate($rules);

        if ($this->editingId) {
            $user = User::query()->with('roles')->findOrFail($this->editingId);

            $existingRoles = collect($user->roles->pluck('name')->map(fn ($n) => (string) $n))
                ->unique()
                ->sort()
                ->values()
                ->all();
            $submittedRoles = collect($validated['formRoles'])->map('strval')->unique()->sort()->values()->all();

            $unchanged = trim((string) $user->name) === trim($validated['formName'])
                && $user->email === $validated['formEmail']
                && (int) $user->organization_id === (int) $validated['formOrganizationId']
                && $existingRoles === $submittedRoles
                && blank($validated['formPassword']);

            if ($unchanged) {
                $this->formNoChangesToast = 'Tidak ada perubahan untuk disimpan.';
                $this->roleExclusiveToast = null;

                return;
            }

            $this->formNoChangesToast = null;

            $user->update([
                'name' => $validated['formName'],
                'email' => $validated['formEmail'],
                'organization_id' => $validated['formOrganizationId'],
            ]);

            if (filled($validated['formPassword'])) {
                $user->update(['password' => Hash::make($validated['formPassword'])]);
            }

            $user->syncRoles($validated['formRoles']);
            session()->flash('toast_success', 'Pengguna berhasil diperbarui.');
        } else {
            $user = User::query()->create([
                'public_id' => (string) Str::uuid(),
                'name' => $validated['formName'],
                'email' => $validated['formEmail'],
                'password' => Hash::make($validated['formPassword']),
                'organization_id' => $validated['formOrganizationId'],
            ]);

            $user->syncRoles($validated['formRoles']);
            session()->flash('toast_success', 'Pengguna berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetPage();
    }

    public function openDeleteModal(int $userId): void
    {
        abort_unless(auth()->user()?->can('user.delete'), 403);
        abort_if($userId === auth()->id(), 403);

        $this->deletingId = $userId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function deleteUser(): void
    {
        abort_unless(auth()->user()?->can('user.delete'), 403);
        abort_if($this->deletingId === auth()->id(), 403);

        $user = User::query()->findOrFail($this->deletingId);
        $user->delete();

        session()->flash('toast_success', 'Pengguna berhasil dihapus.');
        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function render()
    {
        $sortColumn = in_array($this->sortBy, ['name', 'email', 'created_at'], true) ? $this->sortBy : 'name';
        $sortDir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $users = User::query()
            ->with(['organization', 'roles'])
            ->when($this->search !== '', function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($sortColumn, $sortDir)
            ->paginate(10);

        return view('livewire.pages.admin.users.index-page', [
            'users' => $users,
            'organizations' => Organization::query()->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }
}
