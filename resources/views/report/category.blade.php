<x-dynamic-component :component="auth()->check() ? 'app-layout' : 'public-layout'">
    <flux:main container>
        <x-dynamic-breadcrumb current="Buat Tiket" />
        @include('report.partials.category-content')
    </flux:main>
</x-dynamic-component>
