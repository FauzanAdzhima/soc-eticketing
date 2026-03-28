<?php

namespace App\Livewire\Pages\Admin\Organizations;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
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
        abort_unless(auth()->user()?->can('opd.create'), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->formName = '';
        $this->showFormModal = true;
    }

    public function openEditModal(int $organizationId): void
    {
        abort_unless(auth()->user()?->can('opd.update'), 403);
        $org = Organization::query()->findOrFail($organizationId);
        $this->resetValidation();
        $this->editingId = $org->id;
        $this->formName = $org->name;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function saveOrganization(): void
    {
        if ($this->editingId) {
            abort_unless(auth()->user()?->can('opd.update'), 403);
        } else {
            abort_unless(auth()->user()?->can('opd.create'), 403);
        }

        $rules = [
            'formName' => [
                'required',
                'string',
                'max:255',
                $this->editingId
                    ? Rule::unique('organizations', 'name')->ignore($this->editingId)
                    : Rule::unique('organizations', 'name'),
            ],
        ];

        $validated = $this->validate($rules);

        if ($this->editingId) {
            Organization::query()->whereKey($this->editingId)->update(['name' => $validated['formName']]);
            session()->flash('toast_success', 'Organisasi berhasil diperbarui.');
        } else {
            Organization::query()->create(['name' => $validated['formName']]);
            session()->flash('toast_success', 'Organisasi berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetPage();
    }

    public function openDeleteModal(int $organizationId): void
    {
        abort_unless(auth()->user()?->can('opd.delete'), 403);
        $this->deletingId = $organizationId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function deleteOrganization(): void
    {
        abort_unless(auth()->user()?->can('opd.delete'), 403);

        $org = Organization::query()->withCount('users')->findOrFail($this->deletingId);

        if ($org->users_count > 0) {
            session()->flash('toast_error', 'Organisasi masih memiliki pengguna; tidak dapat dihapus.');
            $this->closeDeleteModal();

            return;
        }

        $org->delete();
        session()->flash('toast_success', 'Organisasi berhasil dihapus.');
        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function render()
    {
        $sortColumn = in_array($this->sortBy, ['name', 'created_at'], true) ? $this->sortBy : 'name';
        $sortDir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $organizations = Organization::query()
            ->withCount('users')
            ->when($this->search !== '', function (Builder $q): void {
                $q->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderBy($sortColumn, $sortDir)
            ->paginate(10);

        return view('livewire.pages.admin.organizations.index-page', [
            'organizations' => $organizations,
        ]);
    }
}
