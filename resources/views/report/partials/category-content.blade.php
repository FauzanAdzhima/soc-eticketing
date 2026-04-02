<section>
    <flux:heading size="xl">Pilih Kategori Layanan</flux:heading>

    <div class="mt-6">
        <livewire:ticket-create-form />
    </div>
</section>

<flux:separator class="my-8" />

<div class="mt-6 rounded-xl bg-[#eaeff8] p-6 dark:bg-zinc-800/60">
    <div class="flex flex-col gap-6 md:flex-row md:items-center">
        <div class="flex justify-center md:w-1/5 md:justify-start">
            <img src="https://www.svgrepo.com/show/474372/code.svg" class="h-28 w-auto" alt="" aria-hidden="true">
        </div>
        <div class="text-center md:w-3/5 md:text-left">
            <flux:heading size="lg">Dapatkan Panduan Kirim Aduan Portal di Kepriprov-CSIRT</flux:heading>
            <flux:text class="mt-3">
                Masih bingung cara kirim aduan portal? Unduh panduan ticketing Kepriprov-CSIRT sekarang juga.
            </flux:text>
        </div>
        <div class="flex justify-center md:w-1/5 md:justify-end">
            <flux:button variant="primary" class="p-3" icon:trailing="arrow-down-tray">
                Unduh Panduan
            </flux:button>
        </div>
    </div>
</div>

@include('report.pgp-section')
