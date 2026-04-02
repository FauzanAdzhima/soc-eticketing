<header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 text-zinc-900 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95 dark:text-zinc-100"
    x-data="{ mobileOpen: false }">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3">
        <a href="https://csirt.kepriprov.go.id/" class="flex items-center gap-3">
            <img class="h-10 w-auto" alt="Logo CSIRT - KEPRIPROV"
                src="https://cms.kepriprov.go.id/api/files/uploads/2026/03/61c98046-c741-4501-be78-9144db1dd199_thumb.webp">
            <div class="leading-tight">
                <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">CSIRT - KEPRIPROV</p>
                <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Provinsi Kepulauan Riau</p>
            </div>
        </a>

        <nav class="hidden items-center gap-1 lg:flex">
            <a href="https://csirt.kepriprov.go.id/"
                class="rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">Beranda</a>

            <div class="group relative py-2 -my-2">
                <button
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">
                    <span>Profil</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.47a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div
                    class="invisible absolute left-0 top-full z-50 min-w-56 pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                    <div class="rounded-md border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/laman/visi-dan-misi">Visi dan Misi</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/laman/tugas-pokok-fungsi">Tugas dan Fungsi</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/laman/indikator-kinerja-utama">Indikator Kinerja</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/laman/struktur-organisasi">Struktur Organisasi</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/laman/profil-pimpinan">Profil Pimpinan</a>
                    </div>
                </div>
            </div>

            <a href="https://csirt.kepriprov.go.id/layanan"
                class="rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">Layanan</a>

            <div class="group relative py-2 -my-2">
                <button
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">
                    <span>Publikasi</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.47a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div
                    class="invisible absolute left-0 top-full z-50 min-w-56 pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                    <div class="rounded-md border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/berita">Artikel / Berita</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/pengumuman">Pengumuman</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/dokumen">Daftar Dokumen</a>
                        <a class="block rounded px-3 py-2 text-sm text-zinc-700 hover:bg-blue-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            href="https://csirt.kepriprov.go.id/galeri">Foto & Video</a>
                    </div>
                </div>
            </div>

            <a href="https://ppid.kepriprov.go.id/" target="_blank"
                class="rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">PPID</a>
            <a href="https://csirt.kepriprov.go.id/kontak"
                class="rounded-md px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white">Hubungi
                Kami</a>
        </nav>

        <div class="flex items-center gap-2">
            <x-theme-toggle compact />

            <button type="button"
                class="inline-flex rounded-md border border-zinc-300 p-2 text-zinc-700 hover:bg-zinc-100 lg:hidden dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800"
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

    <div class="border-t border-zinc-200 px-4 py-3 lg:hidden dark:border-zinc-800" x-show="mobileOpen" x-cloak>
        <nav class="space-y-1 text-zinc-800 dark:text-zinc-100">
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800"
                href="https://csirt.kepriprov.go.id/">Beranda</a>
            <details class="rounded-md">
                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800">Profil</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/laman/visi-dan-misi">Visi dan Misi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/laman/tugas-pokok-fungsi">Tugas dan Fungsi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/laman/indikator-kinerja-utama">Indikator Kinerja</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/laman/struktur-organisasi">Struktur Organisasi</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/laman/profil-pimpinan">Profil Pimpinan</a>
                </div>
            </details>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800"
                href="https://csirt.kepriprov.go.id/layanan">Layanan</a>
            <details class="rounded-md">
                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800">Publikasi</summary>
                <div class="space-y-1 px-2 pb-2">
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/berita">Artikel / Berita</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/pengumuman">Pengumuman</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/dokumen">Daftar Dokumen</a>
                    <a class="block rounded px-3 py-1.5 text-sm hover:bg-blue-50 dark:hover:bg-zinc-800"
                        href="https://csirt.kepriprov.go.id/galeri">Foto & Video</a>
                </div>
            </details>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800"
                href="https://ppid.kepriprov.go.id/" target="_blank">PPID</a>
            <a class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-blue-50 dark:hover:bg-zinc-800"
                href="https://csirt.kepriprov.go.id/kontak">Hubungi Kami</a>
        </nav>
    </div>
</header>
