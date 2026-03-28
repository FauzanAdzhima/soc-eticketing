<?php

namespace App\Livewire\Pages\Admin\IncidentCategories;

use App\Models\IncidentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
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

    public string $formSlug = '';

    public string $formDescription = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        $allowed = ['name', 'slug', 'created_at'];
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
        abort_unless(auth()->user()?->can('incident-category.create'), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->formName = '';
        $this->formSlug = '';
        $this->formDescription = '';
        $this->showFormModal = true;
    }

    public function openEditModal(int $categoryId): void
    {
        abort_unless(auth()->user()?->can('incident-category.update'), 403);
        $cat = IncidentCategory::query()->findOrFail($categoryId);
        $this->resetValidation();
        $this->editingId = $cat->id;
        $this->formName = $cat->name;
        $this->formSlug = $cat->slug;
        $this->formDescription = (string) ($cat->description ?? '');
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function saveCategory(): void
    {
        if ($this->editingId) {
            abort_unless(auth()->user()?->can('incident-category.update'), 403);
        } else {
            abort_unless(auth()->user()?->can('incident-category.create'), 403);
        }

        $validated = $this->validate([
            'formName' => ['required', 'string', 'max:255'],
            'formDescription' => ['nullable', 'string', 'max:2000'],
            'formSlug' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = filled(trim($this->formSlug))
            ? Str::slug(trim($this->formSlug))
            : Str::slug($validated['formName']);

        if ($slug === '') {
            $this->addError('formSlug', 'Slug tidak valid untuk nama ini.');

            return;
        }

        $slugRule = Rule::unique('incident_categories', 'slug');
        if ($this->editingId) {
            $slugRule = $slugRule->ignore($this->editingId);
        }

        $slugValidator = Validator::make(
            ['slug' => $slug],
            ['slug' => ['required', 'string', 'max:255', $slugRule]],
        );

        if ($slugValidator->fails()) {
            foreach ($slugValidator->errors()->get('slug') as $message) {
                $this->addError('formSlug', $message);
            }

            return;
        }

        if ($this->editingId) {
            IncidentCategory::query()->whereKey($this->editingId)->update([
                'name' => $validated['formName'],
                'slug' => $slug,
                'description' => $validated['formDescription'] ?: null,
            ]);
            session()->flash('toast_success', 'Kategori insiden berhasil diperbarui.');
        } else {
            IncidentCategory::query()->create([
                'name' => $validated['formName'],
                'slug' => $slug,
                'description' => $validated['formDescription'] ?: null,
            ]);
            session()->flash('toast_success', 'Kategori insiden berhasil ditambahkan.');
        }

        $this->showFormModal = false;
        $this->resetPage();
    }

    public function openDeleteModal(int $categoryId): void
    {
        abort_unless(auth()->user()?->can('incident-category.delete'), 403);
        $this->deletingId = $categoryId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function deleteCategory(): void
    {
        abort_unless(auth()->user()?->can('incident-category.delete'), 403);

        $cat = IncidentCategory::query()->withCount('tickets')->findOrFail($this->deletingId);

        if ($cat->tickets_count > 0) {
            session()->flash('toast_error', 'Kategori masih dipakai oleh tiket; tidak dapat dihapus.');
            $this->closeDeleteModal();

            return;
        }

        $cat->delete();
        session()->flash('toast_success', 'Kategori insiden berhasil dihapus.');
        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function render()
    {
        $sortColumn = in_array($this->sortBy, ['name', 'slug', 'created_at'], true) ? $this->sortBy : 'name';
        $sortDir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $categories = IncidentCategory::query()
            ->withCount('tickets')
            ->when($this->search !== '', function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($sortColumn, $sortDir)
            ->paginate(10);

        return view('livewire.pages.admin.incident-categories.index-page', [
            'categories' => $categories,
        ]);
    }
}
