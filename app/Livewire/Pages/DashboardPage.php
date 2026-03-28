<?php

namespace App\Livewire\Pages;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class DashboardPage extends Component
{
    /** @var list<int> */
    public const OPD_TOP_LIMIT_OPTIONS = [5, 10, 15, 20];

    public string $activeChart = 'opd';

    public int $opdTopLimit = 5;

    public function mount(): void
    {
        $this->normalizeOpdTopLimit();
        $this->syncActiveChart();
    }

    public function updatedActiveChart(string $value): void
    {
        $allowed = $this->availableChartIds();
        if ($allowed === [] || in_array($value, $allowed, true)) {
            return;
        }
        $this->activeChart = $allowed[0];
    }

    public function updatedOpdTopLimit(mixed $value): void
    {
        $n = (int) $value;
        $this->opdTopLimit = in_array($n, self::OPD_TOP_LIMIT_OPTIONS, true) ? $n : 5;
    }

    /**
     * @return list<string>
     */
    private function availableChartIds(): array
    {
        $user = auth()->user();
        $ids = [];
        if ($user?->can('opd.view')) {
            $ids[] = 'opd';
        }
        if ($user?->can('user.view')) {
            $ids[] = 'team';
        }

        return $ids;
    }

    private function syncActiveChart(): void
    {
        $allowed = $this->availableChartIds();
        if ($allowed === []) {
            return;
        }
        if (! in_array($this->activeChart, $allowed, true)) {
            $this->activeChart = $allowed[0];
        }
    }

    private function normalizeOpdTopLimit(): void
    {
        if (! in_array($this->opdTopLimit, self::OPD_TOP_LIMIT_OPTIONS, true)) {
            $this->opdTopLimit = 5;
        }
    }

    /**
     * @return array{type: string, labels: list<string>, values: list<int>}|null
     */
    private function buildChartPayload(bool $canOpdChart, bool $canTeamChart): ?array
    {
        if ($canOpdChart && $this->activeChart === 'opd') {
            $orgs = Organization::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->orderBy('name')
                ->limit($this->opdTopLimit)
                ->get();

            return [
                'type' => 'bar',
                'labels' => $orgs->map(fn (Organization $o) => Str::limit($o->name, 40))->values()->all(),
                'values' => $orgs->pluck('users_count')->map(fn ($c) => (int) $c)->values()->all(),
            ];
        }

        if ($canTeamChart && $this->activeChart === 'team') {
            $roles = Role::query()
                ->where('guard_name', 'web')
                ->withCount('users')
                ->orderByDesc('users_count')
                ->orderBy('name')
                ->get()
                ->filter(fn (Role $role) => $role->users_count > 0);

            return [
                'type' => 'doughnut',
                'labels' => $roles->map(fn (Role $r) => role_label($r->name) ?: $r->name)->values()->all(),
                'values' => $roles->pluck('users_count')->map(fn ($c) => (int) $c)->values()->all(),
            ];
        }

        return null;
    }

    public function render(): View
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can('user.view')) {
            $stats[] = [
                'label' => 'Pengguna',
                'count' => User::query()->count(),
                'manageUrl' => route('admin.users.index'),
            ];
        }

        if ($user?->can('opd.view')) {
            $stats[] = [
                'label' => 'Organisasi (OPD)',
                'count' => Organization::query()->count(),
                'manageUrl' => route('admin.organizations.index'),
            ];
        }

        if ($user?->can('role.view')) {
            $stats[] = [
                'label' => 'Role',
                'count' => Role::query()->count(),
                'manageUrl' => route('admin.roles.index'),
            ];
        }

        if ($user?->can('incident-category.view')) {
            $stats[] = [
                'label' => 'Kategori insiden',
                'count' => IncidentCategory::query()->count(),
                'manageUrl' => route('admin.incident-categories.index'),
            ];
        }

        $canOpdChart = $user?->can('opd.view') ?? false;
        $canTeamChart = $user?->can('user.view') ?? false;
        $showChartSection = $canOpdChart || $canTeamChart;

        $totalOrganizations = $canOpdChart ? Organization::query()->count() : null;
        $chartPayload = $showChartSection ? $this->buildChartPayload($canOpdChart, $canTeamChart) : null;
        $opdChartRowCount = ($chartPayload !== null && $chartPayload['type'] === 'bar')
            ? count($chartPayload['labels'])
            : null;

        return view('livewire.pages.dashboard-page', [
            'stats' => $stats,
            'showChartSection' => $showChartSection,
            'canOpdChart' => $canOpdChart,
            'canTeamChart' => $canTeamChart,
            'chartPayload' => $chartPayload,
            'totalOrganizations' => $totalOrganizations,
            'opdChartRowCount' => $opdChartRowCount,
            'opdTopLimitOptions' => self::OPD_TOP_LIMIT_OPTIONS,
        ]);
    }
}
