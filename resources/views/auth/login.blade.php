<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - CSIRT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-950 antialiased">
    <div class="flex min-h-screen flex-row">
        <section class="relative hidden w-2/3 lg:block">
            <div class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('/images/indonesia-flag-texture.jpg');">
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            </div>
        </section>

        <section class="flex w-full flex-col items-center justify-center bg-zinc-900 px-8 lg:w-1/3">
            <div class="w-full max-w-sm space-y-8">
                <a href="{{ route('home') }}"
                    class="inline-flex items-center gap-2 text-sm font-medium text-zinc-300 hover:text-white">
                    <span aria-hidden="true">&larr;</span>
                    <span>Kembali</span>
                </a>

                <x-auth-session-status :status="session('status')" />

                <div class="flex flex-col items-center space-y-4">
                    <img src="{{ asset('images/logo_csirt.webp') }}" alt="CSIRT Logo" class="h-24 w-auto">
                    <div class="text-center">
                        <p class="text-sm text-zinc-300">Login sesuai email dan password</p>
                    </div>
                </div>

                <form action="{{ route('login') }}" method="POST" class="space-y-6">
                    @csrf

                    <flux:input type="email" name="email" id="email" label="Email"
                        placeholder="email@kepriprov.go.id" :value="old('email')" required autofocus
                        autocomplete="email" icon="envelope" />

                    <flux:input type="password" name="password" label="Kata Sandi" placeholder="Masukkan kata sandi"
                        required autocomplete="current-password" icon="key" viewable />

                    <flux:button type="submit" variant="primary" class="w-full py-3">
                        Masuk
                    </flux:button>
                </form>

                <div class="mt-12 text-center text-xs text-zinc-500">
                    © 2026 - Kepriprov-CSIRT
                </div>
            </div>
        </section>
    </div>

    @fluxScripts
</body>

</html>
