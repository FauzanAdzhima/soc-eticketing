<div class="space-y-4">
    @if (session()->has('toast_success'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none text-emerald-700/70 hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200" aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif
    @if (session()->has('toast_error'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-900/20 dark:text-red-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_error') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none text-red-700/70 hover:text-red-900 dark:text-red-300/70 dark:hover:text-red-200" aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Daftar Organisasi (OPD)</flux:heading>
        @can('opd.create')
            <flux:button size="sm" variant="primary" wire:click="openCreateModal">Tambah organisasi</flux:button>
        @endcan
    </div>

    <flux:card class="min-w-0 p-4 sm:p-5">
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama organisasi…" />
        </div>

        <div class="max-w-full overflow-x-auto overscroll-x-contain rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full min-w-max divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            <button type="button" wire:click="sortByColumn('name')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                Nama
                                @if ($sortBy === 'name')
                                    <span class="text-xs font-normal">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Jumlah pengguna</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            <button type="button" wire:click="sortByColumn('created_at')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                Dibuat
                                @if ($sortBy === 'created_at')
                                    <span class="text-xs font-normal">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($organizations as $org)
                        <tr wire:key="org-{{ $org->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="max-w-xs whitespace-nowrap px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100"><span class="block truncate">{{ $org->name }}</span></td>
                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $org->users_count }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                {{ $org->created_at?->format('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <div class="inline-flex flex-nowrap justify-end gap-1">
                                    @can('opd.update')
                                        <flux:button size="sm" variant="ghost" wire:click="openEditModal({{ $org->id }})">Ubah</flux:button>
                                    @endcan
                                    @can('opd.delete')
                                        <flux:button size="sm" variant="danger" wire:click="openDeleteModal({{ $org->id }})">Hapus</flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">Tidak ada data organisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $organizations->links() }}
        </div>
    </flux:card>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $editingId ? 'Ubah organisasi' : 'Tambah organisasi' }}</h3>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama</label>
                    <input type="text" wire:model="formName"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                    @error('formName')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeFormModal">Batal</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="saveOrganization">Simpan</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Hapus organisasi</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Organisasi tanpa pengguna dapat dihapus. Lanjutkan?</p>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeDeleteModal">Batal</flux:button>
                    <flux:button size="sm" variant="danger" wire:click="deleteOrganization">Hapus</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
