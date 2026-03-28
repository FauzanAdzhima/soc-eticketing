<div class="space-y-4">
    @if (session()->has('toast_success'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none text-emerald-700/70 hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Daftar Pengguna</flux:heading>
        @can('user.create')
            <flux:button size="sm" variant="primary" wire:click="openCreateModal">Tambah pengguna</flux:button>
        @endcan
    </div>

    <flux:card class="p-4 sm:p-5">
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau email…" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
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
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            <button type="button" wire:click="sortByColumn('email')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                Email
                                @if ($sortBy === 'email')
                                    <span class="text-xs font-normal">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 md:table-cell">Organisasi</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 lg:table-cell">Role</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 lg:table-cell">
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
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                            <td class="hidden px-3 py-2 text-zinc-600 dark:text-zinc-300 md:table-cell">{{ $user->organization?->name ?? '—' }}</td>
                            <td class="hidden max-w-xs truncate px-3 py-2 text-zinc-600 dark:text-zinc-300 lg:table-cell">
                                {{ $user->roles->map(fn ($r) => role_label($r->name))->filter()->join(', ') ?: '—' }}
                            </td>
                            <td class="hidden whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300 lg:table-cell">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1">
                                    @can('user.update')
                                        <flux:button size="sm" variant="ghost" wire:click="openEditModal({{ $user->id }})">Ubah</flux:button>
                                    @endcan
                                    @can('user.delete')
                                        @if ($user->id !== auth()->id())
                                            <flux:button size="sm" variant="danger" wire:click="openDeleteModal({{ $user->id }})">Hapus</flux:button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">Tidak ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </flux:card>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $editingId ? 'Ubah pengguna' : 'Tambah pengguna' }}
                </h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Lengkapi data akun dan penempatan organisasi.</p>

                @if (filled($roleExclusiveToast))
                    <div wire:key="role-exclusive-toast"
                        class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100">
                        <div class="flex items-start justify-between gap-3">
                            <span>{{ $roleExclusiveToast }}</span>
                            <button type="button" wire:click="dismissRoleExclusiveToast"
                                class="shrink-0 text-base leading-none text-amber-800/70 hover:text-amber-950 dark:text-amber-200/80 dark:hover:text-amber-50"
                                aria-label="Tutup">&times;</button>
                        </div>
                    </div>
                @endif

                @if (filled($formNoChangesToast))
                    <div wire:key="form-no-changes-toast"
                        class="mt-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200">
                        <div class="flex items-start justify-between gap-3">
                            <span>{{ $formNoChangesToast }}</span>
                            <button type="button" wire:click="dismissFormNoChangesToast"
                                class="shrink-0 text-base leading-none text-sky-700/70 hover:text-sky-900 dark:text-sky-300/70 dark:hover:text-sky-100"
                                aria-label="Tutup">&times;</button>
                        </div>
                    </div>
                @endif

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama</label>
                        <input type="text" wire:model="formName"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                        @error('formName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                        <input type="email" wire:model="formEmail"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                        @error('formEmail')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Organisasi</label>
                        <select wire:model.number="formOrganizationId"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('formOrganizationId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
                        <div
                            class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            @forelse ($roles as $role)
                                <label
                                    class="flex cursor-pointer items-start gap-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                                    @if (filled($role->desc)) title="{{ $role->desc }}" @endif>
                                    <input type="checkbox" value="{{ $role->name }}" wire:model.live="formRoles"
                                        class="mt-0.5 rounded border-zinc-300 text-zinc-900 dark:border-zinc-600" />
                                    <span class="font-medium">{{ role_label($role->name) }}</span>
                                    <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">({{ $role->name }})</span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada role di database.</p>
                            @endforelse
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Centang satu atau lebih role untuk pengguna ini. Role <span class="font-medium text-zinc-600 dark:text-zinc-300">Admin</span>, <span class="font-medium text-zinc-600 dark:text-zinc-300">Koordinator</span>, dan <span class="font-medium text-zinc-600 dark:text-zinc-300">Pimpinan</span> tidak dapat digabung dengan role lain (otomatis hanya satu role).</p>
                        @error('formRoles')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('formRoles.*')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700"
                        wire:key="user-password-panel-{{ $editingId ?? 'new' }}"
                        x-data="{ pwdOpen: {{ $editingId ? 'false' : 'true' }} }">
                        <button type="button" @click="pwdOpen = !pwdOpen"
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-zinc-800 transition hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800/80"
                            :aria-expanded="pwdOpen">
                            <span>
                                {{ $editingId ? 'Password baru (opsional)' : 'Password akun' }}
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform duration-200 dark:text-zinc-400"
                                :class="{ 'rotate-180': pwdOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="pwdOpen" x-cloak x-transition
                            class="border-t border-zinc-200 px-3 pb-3 pt-2 dark:border-zinc-700">
                            <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($editingId)
                                    Kosongkan jika tidak ingin mengganti password.
                                @else
                                    Wajib diisi minimal 8 karakter untuk pengguna baru.
                                @endif
                            </p>
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
                                    <input type="password" wire:model="formPassword" autocomplete="new-password"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                                    @error('formPassword')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Konfirmasi password</label>
                                    <input type="password" wire:model="formPasswordConfirmation" autocomplete="new-password"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                                    @error('formPasswordConfirmation')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeFormModal">Batal</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="saveUser">Simpan</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Hapus pengguna</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Tindakan ini tidak dapat dibatalkan. Lanjutkan?</p>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeDeleteModal">Batal</flux:button>
                    <flux:button size="sm" variant="danger" wire:click="deleteUser">Hapus</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
