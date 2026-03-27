<x-public-layout>
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

                    <flux:button variant="primary" href="{{ route('ticket.create') }}" icon:trailing="arrow-right" class="my-2">Buat Tiket</flux:button>
                </flux:card>

                <flux:separator vertical class="mx-8"></flux:separator>

                <flux:card class="flex-1">
                    <flux:heading size="lg">Cari Tiket</flux:heading>

                    <flux:button variant="primary" class="my-2">Cari Tiket Anda</flux:button>
                </flux:card>
            </div>
        </section>

        @include('report.guide-section')

        @include('report.pgp-section')
    </flux:main>
</x-public-layout>
