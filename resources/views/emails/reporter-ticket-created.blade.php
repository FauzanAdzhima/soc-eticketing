<x-mail::message>
# Laporan Anda telah diterima

Halo {{ $reporterName }},

Terima kasih atas laporan Anda. Tiket **{{ $ticketNumber }}** telah kami catat dengan judul:

**{{ $title }}**

Anda dapat mengikuti percakapan terkait tiket melalui tautan berikut:

<x-mail::button :url="$trackUrl">
Buka percakapan tiket
</x-mail::button>

Simpan tautan ini dengan aman dan jangan bagikan kepada pihak yang tidak berwenang.

Jika tombol di atas tidak berfungsi, salin alamat berikut ke peramban Anda:

<x-mail::panel>
{{ $trackUrl }}
</x-mail::panel>

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
