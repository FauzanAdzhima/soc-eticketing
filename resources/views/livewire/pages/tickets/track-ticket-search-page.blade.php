<div>
    <flux:main container>
        <x-dynamic-breadcrumb current="Cari Tiket" />

        <div class="mx-auto max-w-lg space-y-6">
            <div>
                <flux:heading size="xl">Cari Tiket Anda</flux:heading>
                <flux:text class="mt-2">
                    Masukkan nomor tiket (contoh: TIC-2604-ABCD) dan token akses 
                    yang didapat
                    {{-- yang dikirim ke email Anda  --}}
                    saat laporan dibuat. Anda juga dapat memakai ID publik dari tautan lacak jika dimiliki.
                </flux:text>
            </div>

            @error('lookup')
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
                    role="alert">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="lookup" class="space-y-5">
                <flux:input wire:model="ticket_reference" label="Nomor Tiket" placeholder="TIC-0101-ABCD" required
                    autocomplete="off" />

                <flux:input wire:model="token" type="password" label="Token Akses"
                    placeholder="Token dari email konfirmasi" required autocomplete="off" />

                <div class="flex flex-wrap gap-3">
                    <flux:button variant="primary" color="blue" type="submit" icon:trailing="arrow-right"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="lookup">Cari</span>
                        <span wire:loading wire:target="lookup">Memproses…</span>
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('home') }}">
                        Kembali
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:main>
</div>
