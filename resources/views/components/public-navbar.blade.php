<header class="sticky top-0 z-40 border-b border-primary-foreground/15 bg-primary text-primary-foreground"
    x-data="{ mobileOpen: false }">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3">
        <a href="https://csirt.kepriprov.go.id/" class="flex items-center gap-3">
            <img class="h-10 w-auto" alt="Logo CSIRT - KEPRIPROV"
                src="https://cms.kepriprov.go.id/api/files/uploads/2026/03/61c98046-c741-4501-be78-9144db1dd199_thumb.webp">
            <div class="leading-tight">
                <p class="text-sm font-bold text-primary-foreground">CSIRT - KEPRIPROV</p>
                <p class="text-xs font-semibold text-primary-foreground/85">Provinsi Kepulauan Riau</p>
            </div>
        </a>

        <nav class="hidden items-center gap-1 lg:flex">
            <a href="https://csirt.kepriprov.go.id/"
                class="rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">Beranda</a>

            <div class="group relative py-2 -my-2">
                <button
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">
                    <span>Profil</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.47a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div
                    class="invisible absolute left-0 top-full z-50 min-w-56 pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                    <div class="rounded-md border border-border bg-surface p-1 shadow-lg">
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/laman/visi-dan-misi">Visi dan Misi</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/laman/tugas-pokok-fungsi">Tugas dan Fungsi</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/laman/indikator-kinerja-utama">Indikator Kinerja</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/laman/struktur-organisasi">Struktur Organisasi</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/laman/profil-pimpinan">Profil Pimpinan</a>
                    </div>
                </div>
            </div>

            <a href="https://csirt.kepriprov.go.id/layanan"
                class="rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">Layanan</a>

            <div class="group relative py-2 -my-2">
                <button
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">
                    <span>Publikasi</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.47a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div
                    class="invisible absolute left-0 top-full z-50 min-w-56 pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                    <div class="rounded-md border border-border bg-surface p-1 shadow-lg">
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/berita">Artikel / Berita</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/pengumuman">Pengumuman</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/dokumen">Daftar Dokumen</a>
                        <a class="block rounded px-3 py-2 text-sm text-foreground-secondary hover:bg-primary/10 dark:hover:bg-muted dark:hover:text-foreground"
                            href="https://csirt.kepriprov.go.id/galeri">Foto & Video</a>
                    </div>
                </div>
            </div>

            <a href="https://ppid.kepriprov.go.id/" target="_blank"
                class="rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">PPID</a>
            <a href="https://csirt.kepriprov.go.id/kontak"
                class="rounded-md px-3 py-2 text-sm font-semibold text-primary-foreground/95 transition hover:bg-primary-foreground/10 hover:text-primary-foreground">Hubungi
                Kami</a>
        </nav>

        <div class="flex items-center gap-2">
            <x-theme-toggle compact
                class="!border-primary-foreground/35 !bg-primary-foreground/10 !text-primary-foreground hover:!bg-primary-foreground/20 focus-visible:!ring-primary-foreground focus-visible:!ring-offset-2 focus-visible:!ring-offset-primary" />

            <button type="button"
                class="inline-flex rounded-md border border-primary-foreground/35 p-2 text-primary-foreground hover:bg-primary-foreground/10 lg:hidden"
                :aria-expanded="mobileOpen.toString()" aria-label="Buka menu"
                @click="mobileOpen = !mobileOpen">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm.75 3.5a.75.75 0 0 0 0 1.5h12.5a.75.75 0 0 0 0-1.5H3.75Z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <div class="border-t border-primary-foreground/15 px-4 py-3 lg:hidden" x-show="mobileOpen" x-cloak>
        <nav class="space-y-1 text-primary-foreground">
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10"
                href="https://csirt.kepriprov.go.id/">Beranda</a>
            <details class="rounded-md">
                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10">Profil</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/laman/visi-dan-misi">Visi dan Misi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/laman/tugas-pokok-fungsi">Tugas dan Fungsi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/laman/indikator-kinerja-utama">Indikator Kinerja</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/laman/struktur-organisasi">Struktur Organisasi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/laman/profil-pimpinan">Profil Pimpinan</a>
                </div>
            </details>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10"
                href="https://csirt.kepriprov.go.id/layanan">Layanan</a>
            <details class="rounded-md">
                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10">Publikasi</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/berita">Artikel / Berita</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/pengumuman">Pengumuman</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/dokumen">Daftar Dokumen</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-primary-foreground/10"
                        href="https://csirt.kepriprov.go.id/galeri">Foto & Video</a>
                </div>
            </details>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10"
                href="https://ppid.kepriprov.go.id/" target="_blank">PPID</a>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-primary-foreground/10"
                href="https://csirt.kepriprov.go.id/kontak">Hubungi Kami</a>
        </nav>
    </div>
</header>
