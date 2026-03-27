<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Profile Saya</flux:heading>
    </div>

    @if (session()->has('profile_success'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('profile_success') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none text-emerald-700/70 transition hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200"
                    aria-label="Tutup notifikasi">
                    &times;
                </button>
            </div>
        </div>
    @endif

    @if (session()->has('password_success'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('password_success') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none text-emerald-700/70 transition hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200"
                    aria-label="Tutup notifikasi">
                    &times;
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <flux:card class="space-y-4 p-5">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Informasi Akun</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Data akun yang sedang login.</p>
            </div>

            <div class="space-y-2 text-sm">
                <p class="text-zinc-600 dark:text-zinc-300"><span class="font-medium">Nama:</span> {{ $this->user?->name }}</p>
                <p class="text-zinc-600 dark:text-zinc-300"><span class="font-medium">Email:</span> {{ $this->user?->email }}</p>
                <p class="text-zinc-600 dark:text-zinc-300"><span class="font-medium">Organisasi / OPD:</span>
                    {{ $this->user?->organization?->name ?? '-' }}</p>
                <p class="text-zinc-600 dark:text-zinc-300"><span class="font-medium">Role:</span>
                    {{ $this->user?->roles->pluck('name')->join(', ') ?: '-' }}</p>
            </div>

            <div>
                <flux:button size="sm" wire:click="openEditProfileModal">Edit Informasi</flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4 p-5">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Keamanan Akun</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Perbarui password untuk menjaga keamanan akun.</p>
            </div>

            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                Gunakan password minimal 8 karakter.
            </div>

            <div>
                <flux:button size="sm" variant="primary" wire:click="openChangePasswordModal">Ubah Password</flux:button>
            </div>
        </flux:card>
    </div>

    @if ($showEditProfileModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Edit Informasi Akun</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Perbarui nama dan email akun Anda.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Organisasi / OPD</label>
                        <input type="text" value="{{ $this->user?->organization?->name ?? '-' }}" readonly
                            class="w-full cursor-not-allowed rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-500 outline-none ring-1 ring-zinc-200/70 dark:border-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-400 dark:ring-zinc-700/60">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama</label>
                        <input type="text" wire:model.defer="name"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                        <input type="email" wire:model.defer="email"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeEditProfileModal">Batal</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="saveProfile">Simpan</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($showChangePasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ubah Password</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Masukkan password baru akun Anda.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Password Lama</label>
                        <input type="password" wire:model.defer="currentPassword"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('currentPassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Password Baru</label>
                        <input type="password" wire:model.defer="newPassword"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('newPassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Konfirmasi Password</label>
                        <input type="password" wire:model.defer="newPasswordConfirmation"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('newPasswordConfirmation')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="closeChangePasswordModal">Batal</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="savePassword">Simpan Password</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
