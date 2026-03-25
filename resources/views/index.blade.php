<x-app-layout>
    <flux:main container>

        <section id="section-header">
            <flux:heading size="xl" level="1" class="text-center">SINTESIS</flux:heading>
            <flux:text class="mt-2 text-center">Sistem Informasi Pelaporan Insiden Siber Terintegrasi</flux:text>
        </section>

        <flux:separator class="my-6"></flux:separator>

        <section id="section-ticket">
            <div class="flex flex-row gap-4 w-full">
                <flux:card class="flex-1">
                    <flux:heading size="lg">Buat Tiket Baru</flux:heading>

                    <flux:button variant="primary" class="my-2">Buat Tiket</flux:button>
                </flux:card>

                <flux:separator vertical class="mx-8"></flux:separator>

                <flux:card class="flex-1">
                    <flux:heading size="lg">Cari Tiket</flux:heading>

                    <flux:button variant="primary" class="my-2">Cari Tiket Anda</flux:button>
                </flux:card>
            </div>
        </section>

        <section id="section-manual-guide">
            <flux:card class="flex flex-row gap-4 w-full mt-8">
                <flux:card class="flex-1"></flux:card>
                <div>
                    <flux:heading size="lg" class="flex-1">Dapatkan Panduan Kirim Aduan Portal di Kepriprov-CSIRT
                    </flux:heading>
                    <flux:text>Masih bingung cara Kirim Aduan Portal di Kepriprov-CSIRT? Unduh panduan ticketing di
                        Kepriprov-CSIRT sekarang juga!</flux:text>
                </div>
                <flux:button class="flex-1">Unduh Panduan</flux:button>
            </flux:card>
        </section>

        <section id="pgp-ticket">
            <div class="flex flex-row gap-4 w-full mt-8">
                <div>
                    <flux:heading size="xl" class="flex-1">Ingin Lapor Aduan Insiden Siber?</flux:heading>
                    <flux:text>Laporkan segera! Kami akan bekerja sama dengan Anda untuk mengatasi ancaman dan
                        memulihkan
                        keamanan dengan cepat! Silahkan <flux:link>unduh</flux:link> kunci publik kami.</flux:text>
                </div>
                <flux:button class="flex-1">Buat Tiket</flux:button>
            </div>
        </section>
    </flux:main>
</x-app-layout>
