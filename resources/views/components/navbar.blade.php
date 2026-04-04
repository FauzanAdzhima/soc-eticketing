<flux:navbar class="-mb-px max-lg:hidden">
    <flux:navbar.item href="#" current>Beranda</flux:navbar.item>
    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
        class="relative h-full flex items-center">
        <flux:navbar.item class="cursor-pointer" @click.stop="open = !open">Profil</flux:navbar.item>
        <div x-show="open" x-cloak x-transition class="absolute top-full left-0 z-50 min-w-[200px] rounded-md border border-border bg-surface pt-2 shadow-lg">
            <flux:menu.item href="#">Visi dan Misi</flux:menu.item>
            <flux:menu.item href="#">Tugas dan Fungsi</flux:menu.item>
            <flux:menu.item href="#">Indikator Kinerja</flux:menu.item>
            <flux:menu.item href="#">Struktur Organisasi</flux:menu.item>
            <flux:menu.item href="#">Profil Pimpinan</flux:menu.item>
        </div>
    </div>
    <flux:navbar.item href="#">Layanan</flux:navbar.item>
    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
        class="relative h-full flex items-center">
        <flux:navbar.item class="cursor-pointer" @click.stop="open = !open">Publikasi</flux:navbar.item>
        <div x-show="open" x-cloak x-transition class="absolute top-full left-0 z-50 min-w-[200px] rounded-md border border-border bg-surface pt-2 shadow-lg">
            <flux:menu.item href="#">Artikel / Berita</flux:menu.item>
            <flux:menu.item href="#">Pengumuman</flux:menu.item>
            <flux:menu.item href="#">Daftar Dokumen</flux:menu.item>
            <flux:menu.item href="#">Foto & Video</flux:menu.item>
        </div>
    </div>
    <flux:navbar.item href="#">PPID</flux:navbar.item>
    <flux:navbar.item href="#">Hubungi Kami</flux:navbar.item>
</flux:navbar>
