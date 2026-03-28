<?php

namespace App\Livewire\Pages\Admin\Roles;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

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

    public string $formDesc = '';

    /** @var array<int, string> */
    public array $formPermissions = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        $allowed = ['name', 'created_at'];
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
        abort_unless(auth()->user()?->can('role.create'), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->formName = '';
        $this->formDesc = '';
        $this->formPermissions = [];
        $this->showFormModal = true;
    }

    public function openEditModal(int $roleId): void
    {
        abort_unless(auth()->user()?->can('role.update'), 403);
        $role = Role::query()->with('permissions')->findOrFail($roleId);
        $this->resetValidation();
        $this->editingId = $role->id;
        $this->formName = $role->name;
        $this->formDesc = (string) ($role->desc ?? '');
        $this->formPermissions = $role->permissions->pluck('name')->all();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function saveRole(): void
    {
        if ($this->editingId) {
            abort_unless(auth()->user()?->can('role.update'), 403);
        } else {
            abort_unless(auth()->user()?->can('role.create'), 403);
        }

        $permissionRule = Rule::exists('permissions', 'name')->where('guard_name', 'web');

        if ($this->editingId) {
            $validated = $this->validate([
                'formDesc' => ['nullable', 'string', 'max:500'],
                'formPermissions' => ['array'],
                'formPermissions.*' => ['string', $permissionRule],
            ]);

            $role = Role::query()->findOrFail($this->editingId);
            $role->update(['desc' => $validated['formDesc']]);
            $role->syncPermissions($this->formPermissions);
            session()->flash('toast_success', 'Role berhasil diperbarui.');
        } else {
            $validated = $this->validate([
                'formName' => [
                    'required',
                    'string',
                    'max:255',
                    'alpha_dash',
                    Rule::unique('roles', 'name')->where('guard_name', 'web'),
                ],
                'formDesc' => ['nullable', 'string', 'max:500'],
                'formPermissions' => ['array'],
                'formPermissions.*' => ['string', $permissionRule],
            ]);

            $role = Role::query()->create([
                'name' => $validated['formName'],
                'guard_name' => 'web',
                'desc' => $validated['formDesc'] ?? null,
            ]);
            $role->syncPermissions($this->formPermissions);
            session()->flash('toast_success', 'Role berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetPage();
    }

    public function openDeleteModal(int $roleId): void
    {
        abort_unless(auth()->user()?->can('role.delete'), 403);
        $this->deletingId = $roleId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function deleteRole(): void
    {
        abort_unless(auth()->user()?->can('role.delete'), 403);

        $role = Role::query()->withCount('users')->findOrFail($this->deletingId);

        if ($role->users_count > 0) {
            session()->flash('toast_error', 'Role masih digunakan oleh pengguna; tidak dapat dihapus.');
            $this->closeDeleteModal();

            return;
        }

        $role->delete();
        session()->flash('toast_success', 'Role berhasil dihapus.');
        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function render()
    {
        $sortColumn = in_array($this->sortBy, ['name', 'created_at'], true) ? $this->sortBy : 'name';
        $sortDir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search !== '', function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('desc', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($sortColumn, $sortDir)
            ->paginate(10);

        $allPermissionNames = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return view('livewire.pages.admin.roles.index-page', [
            'roles' => $roles,
            'allPermissionNames' => $allPermissionNames,
        ]);
    }
}
