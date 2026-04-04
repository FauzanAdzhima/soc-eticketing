<x-public-layout>
    <flux:main container>

        {{-- <section id="section-header">
            <flux:heading size="xl" level="1" class="text-center">SINTESIS</flux:heading>
            <flux:text class="mt-2 text-center">Sistem Informasi Pelaporan Insiden Siber Terintegrasi</flux:text>
        </section> --}}

        {{-- <flux:separator class="my-6"></flux:separator> --}}

        <section id="section-ticket" class="overflow-hidden">
            <div class="text-center font-sans">
                <flux:heading size="xl" class="font-oswald tracking-wide">TICKETING KEPRIPROV-CSIRT</flux:heading>
                <flux:text class="mx-auto mt-4 max-w-3xl font-sans">
                    Selamat Datang di Pusat Bantuan Pelaporan Insiden Siber Kepriprov-CSIRT.
                </flux:text>
            </div>

            <div class="flex w-full flex-wrap justify-between gap-4 xl:justify-around">
                <div class="min-w-[280px] flex-1">
                    <div
                        class="relative mt-4 min-h-[220px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_4px_6px_rgba(0,0,0,0.08),4px_0_6px_rgba(0,0,0,0.06),-4px_0_6px_rgba(0,0,0,0.06)] dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="relative z-10 flex h-full flex-col justify-between p-6">
                            <flux:heading size="lg">Buat Tiket Baru</flux:heading>

                            <div>
                                <flux:button variant="primary" color="blue" href="{{ route('ticket.create') }}"
                                    icon:trailing="arrow-right" class="mt-8 py-3">
                                    Buat Tiket
                                </flux:button>
                            </div>
                        </div>
                        <img class="pointer-events-none absolute right-0 top-0 h-full w-1/2 object-contain p-4 opacity-90"
                            src="https://www.svgrepo.com/show/474371/disk1.svg" alt="" aria-hidden="true">
                    </div>
                </div>

                <div class="min-w-[280px] flex-1">
                    <div
                        class="relative mt-4 min-h-[220px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_4px_6px_rgba(0,0,0,0.08),4px_0_6px_rgba(0,0,0,0.06),-4px_0_6px_rgba(0,0,0,0.06)] dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="relative z-10 flex h-full flex-col justify-between p-6">
                            <flux:heading size="lg">Cari Tiket</flux:heading>

                            <div>
                                <flux:button variant="primary" color="blue" class="mt-8 py-3" icon:trailing="arrow-right">
                                    Cari Tiket Anda
                                </flux:button>
                            </div>
                        </div>
                        <img class="pointer-events-none absolute right-0 top-0 h-full w-1/2 object-contain p-4 opacity-90"
                            src="https://www.svgrepo.com/show/474392/pc.svg" alt="" aria-hidden="true">
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-[#eaeff8] p-6 dark:bg-zinc-800/60">
                <div class="flex flex-col gap-6 md:flex-row md:items-center">
                    <div class="flex justify-center md:w-1/5 md:justify-start">
                        <img src="https://www.svgrepo.com/show/474372/code.svg" class="h-28 w-auto"
                            alt="" aria-hidden="true">
                    </div>
                    <div class="text-center md:w-3/5 md:text-left">
                        <flux:heading size="lg">Dapatkan Panduan Kirim Aduan Portal di Kepriprov-CSIRT</flux:heading>
                        <flux:text class="mt-3">
                            Masih bingung cara kirim aduan portal? Unduh panduan ticketing Kepriprov-CSIRT sekarang
                            juga.
                        </flux:text>
                    </div>
                    <div class="flex justify-center md:w-1/5 md:justify-end">
                        <flux:button variant="primary" color="blue" class="p-3" icon:trailing="arrow-down-tray">
                            Unduh Panduan
                        </flux:button>
                    </div>
                </div>
            </div>
        </section>

        @include('report.pgp-section')
    </flux:main>
</x-public-layout>
