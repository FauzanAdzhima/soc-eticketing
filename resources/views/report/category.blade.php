<x-app-layout>
    <flux:main container>
        <section>
            <flux:heading size="xl">Pilih Kategori Layanan</flux:heading>

            <div class="mt-6">
                <livewire:ticket-create-form />
            </div>
        </section>

        <flux:separator class="my-8"/>

        @include('report.guide-section')

        @include('report.pgp-section')
    </flux:main>
</x-app-layout>
