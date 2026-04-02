<section id="pgp-ticket" class="mt-10 overflow-hidden">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 pb-6 pt-2 dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
            <div class="relative w-full md:w-4/5">
                <div class="dotted-bg absolute -left-2 -top-2 h-24 w-44 opacity-60 dark:opacity-30" aria-hidden="true"></div>
                <flux:heading size="xl" class="relative">Ingin Lapor Aduan Insiden Siber?</flux:heading>
                <flux:text class="relative mt-3 text-base">
                    Laporkan segera! Kami akan bekerja sama dengan Anda untuk mengatasi ancaman dan memulihkan
                    keamanan dengan cepat. Silahkan
                    <a href="#" class="font-medium text-blue-600 underline underline-offset-2 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        unduh
                    </a>
                    kunci publik kami.
                    <span
                        class="ml-1 inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-semibold text-white">
                        PGP <span class="ml-1 italic">(Pretty Good Privacy)</span>
                    </span>
                </flux:text>
            </div>
            <div class="w-full md:w-auto">
                <flux:button href="{{ route('ticket.create') }}" variant="primary" class="w-full md:w-auto" icon="pencil">
                    Buat Tiket
                </flux:button>
            </div>
        </div>
    </div>
</section>
