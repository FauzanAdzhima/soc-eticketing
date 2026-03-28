<div class="space-y-6">
    <flux:heading size="xl">Dashboard</flux:heading>

    <div
        x-data="{
            open: true,
            totalMs: 12000,
            remainingMs: 12000,
            tickMs: 50,
            timer: null,
            secondsLeft() {
                return Math.max(0, Math.ceil(this.remainingMs / 1000));
            },
            fadeOpacity() {
                return Math.max(0, this.remainingMs / this.totalMs);
            },
            start() {
                this.timer = setInterval(() => {
                    this.remainingMs -= this.tickMs;
                    if (this.remainingMs <= 0) {
                        this.remainingMs = 0;
                        clearInterval(this.timer);
                        this.timer = null;
                        this.open = false;
                    }
                }, this.tickMs);
            },
            close() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                this.open = false;
            },
        }"
        x-init="start()"
        x-show="open"
        x-transition.opacity.duration.200ms
        x-on:livewire:navigating.window="close()"
        role="status"
        class="pointer-events-auto fixed top-18.5 right-4 z-50 max-w-md w-[min(100%,calc(100vw-2rem))]"
    >
        <div :style="{ opacity: fadeOpacity() }">
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-lg dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <span>Selamat datang di dashboard SOC eTicketing.</span>
                        <span
                            class="mt-1.5 block text-xs font-medium tabular-nums text-emerald-800/90 dark:text-emerald-200/90"
                            x-text="'Menutup otomatis dalam ' + secondsLeft() + ' dtk'"
                        ></span>
                    </div>
                    <button
                        type="button"
                        @click="close()"
                        class="shrink-0 text-base leading-none text-emerald-700/70 hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200"
                        aria-label="Tutup"
                    >
                        &times;
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($stats !== [])
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($stats as $stat)
                <flux:card class="flex flex-col gap-3 p-5">
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $stat['label'] }}</p>
                    <p class="text-3xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                        {{ number_format($stat['count']) }}
                    </p>
                    <div class="mt-auto pt-1">
                        <flux:button variant="ghost" size="sm" href="{{ $stat['manageUrl'] }}" wire:navigate>
                            Kelola
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    @if ($showChartSection)
        <flux:card class="p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <flux:heading size="lg">Grafik ringkas</flux:heading>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($activeChart === 'opd' && $canOpdChart)
                            OPD dengan jumlah pengguna terbanyak (bukan total seluruh OPD pada batang).
                        @elseif ($activeChart === 'team' && $canTeamChart)
                            Jumlah pengguna per role (satu orang dapat terhitung di lebih dari satu role).
                        @endif
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    @if ($canOpdChart && $canTeamChart)
                        <div class="inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700">
                            <flux:button
                                type="button"
                                size="sm"
                                variant="{{ $activeChart === 'opd' ? 'primary' : 'ghost' }}"
                                wire:click="$set('activeChart', 'opd')"
                                wire:key="chart-tab-opd"
                            >
                                Pengguna per OPD
                            </flux:button>
                            <flux:button
                                type="button"
                                size="sm"
                                variant="{{ $activeChart === 'team' ? 'primary' : 'ghost' }}"
                                wire:click="$set('activeChart', 'team')"
                                wire:key="chart-tab-team"
                            >
                                Komposisi tim
                            </flux:button>
                        </div>
                    @endif
                    @if ($activeChart === 'opd' && $canOpdChart)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="whitespace-nowrap">Tampilkan top</span>
                            <select
                                wire:model.live="opdTopLimit"
                                class="rounded-lg border border-zinc-300 bg-white px-2.5 py-1.5 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                            >
                                @foreach ($opdTopLimitOptions as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                            <span class="whitespace-nowrap">OPD</span>
                        </label>
                    @endif
                </div>
            </div>

            @if ($activeChart === 'opd' && $canOpdChart && $totalOrganizations !== null)
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    Total {{ number_format($totalOrganizations) }} organisasi terdaftar.
                    @if ($opdChartRowCount !== null)
                        Grafik memuat {{ $opdChartRowCount }} OPD teratas menurut jumlah pengguna.
                    @endif
                </p>
            @endif

            @if ($chartPayload === null)
                <p class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">Tidak ada data grafik untuk ditampilkan.</p>
            @elseif ($chartPayload['labels'] === [])
                <p class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">Belum ada data untuk grafik ini.</p>
            @else
                <div
                    class="relative mt-6 h-[min(24rem,55vh)] w-full"
                    wire:key="dashboard-chart-{{ $activeChart }}-{{ $opdTopLimit }}"
                    x-data="{
                        chart: null,
                        destroyChart() {
                            if (this.chart) {
                                this.chart.destroy();
                                this.chart = null;
                            }
                        },
                        palette: [
                            'rgba(59, 130, 246, 0.88)',
                            'rgba(16, 185, 129, 0.88)',
                            'rgba(245, 158, 11, 0.88)',
                            'rgba(239, 68, 68, 0.88)',
                            'rgba(139, 92, 246, 0.88)',
                            'rgba(236, 72, 153, 0.88)',
                            'rgba(20, 184, 166, 0.88)',
                            'rgba(99, 102, 241, 0.88)',
                        ],
                        run() {
                            this.destroyChart();
                            this.$nextTick(() => {
                                const Chart = window.Chart;
                                const canvas = this.$refs.chartCanvas;
                                const p = {{ \Illuminate\Support\Js::from($chartPayload) }};
                                if (!Chart || !canvas || !p || !p.type) {
                                    return;
                                }
                                const isDark = document.documentElement.classList.contains('dark');
                                const tickColor = isDark ? '#a1a1aa' : '#52525b';
                                const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
                                if (p.type === 'bar') {
                                    this.chart = new Chart(canvas, {
                                        type: 'bar',
                                        data: {
                                            labels: p.labels,
                                            datasets: [
                                                {
                                                    label: 'Pengguna',
                                                    data: p.values,
                                                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                                    borderRadius: 4,
                                                },
                                            ],
                                        },
                                        options: {
                                            indexAxis: 'y',
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: {
                                                    beginAtZero: true,
                                                    ticks: { color: tickColor, precision: 0 },
                                                    grid: { color: gridColor },
                                                },
                                                y: {
                                                    ticks: { color: tickColor },
                                                    grid: { display: false },
                                                },
                                            },
                                        },
                                    });
                                } else if (p.type === 'doughnut') {
                                    const bg = p.labels.map((_, i) => this.palette[i % this.palette.length]);
                                    this.chart = new Chart(canvas, {
                                        type: 'doughnut',
                                        data: {
                                            labels: p.labels,
                                            datasets: [
                                                {
                                                    data: p.values,
                                                    backgroundColor: bg,
                                                    borderWidth: 0,
                                                },
                                            ],
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: {
                                                    position: 'bottom',
                                                    labels: { color: tickColor, boxWidth: 12 },
                                                },
                                            },
                                        },
                                    });
                                }
                            });
                        },
                    }"
                    x-init="run()"
                    x-on:livewire:navigating.window="destroyChart()"
                >
                    <canvas x-ref="chartCanvas" class="h-full w-full"></canvas>
                </div>
            @endif
        </flux:card>
    @endif
</div>
