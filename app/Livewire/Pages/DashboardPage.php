<?php

namespace App\Livewire\Pages;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    public ?int $dashboardAssignmentPollBaseline = null;

    public bool $showDashboardNewAssignmentBanner = false;

    /** Toast selamat datang: sekali setelah login / registrasi (lihat AuthenticatedSessionController & RegisteredUserController). */
    public bool $showWelcomeToast = false;

    public function mount(): void
    {
        $this->showWelcomeToast = (bool) session()->pull('show_dashboard_welcome_once', false);
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
     * Polling ringkas untuk analis: deteksi jumlah tiket dengan penugasan aktif bertambah.
     */
    public function refreshDashboardAssignmentSignal(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->can('ticket.analyze')) {
            return;
        }

        $current = (clone $this->analystDashboardTicketQuery($user))->count();

        if ($this->dashboardAssignmentPollBaseline === null) {
            $this->dashboardAssignmentPollBaseline = $current;

            return;
        }

        if ($current > $this->dashboardAssignmentPollBaseline) {
            $this->showDashboardNewAssignmentBanner = true;
        }

        $this->dashboardAssignmentPollBaseline = $current;
    }

    public function dismissDashboardNewAssignmentBanner(): void
    {
        $this->showDashboardNewAssignmentBanner = false;
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

        $showAnalystTicketStatsCard = false;
        $analystTicketAssignedCount = null;
        $analystTicketAnalyzedCount = null;
        $analystTicketPendingAnalysisCount = null;
        $analystChartPayload = null;
        if ($user instanceof User && $user->can('ticket.analyze')) {
            $showAnalystTicketStatsCard = true;
            $baseAnalyst = $this->analystDashboardTicketQuery($user);
            $analystTicketAssignedCount = (clone $baseAnalyst)->count();
            $analystTicketAnalyzedCount = (clone $baseAnalyst)
                ->whereHas('analyses', function (Builder $q) use ($user): void {
                    $q->where('performed_by', $user->id);
                })
                ->count();
            $analystTicketPendingAnalysisCount = (clone $baseAnalyst)
                ->whereDoesntHave('analyses', function (Builder $q) use ($user): void {
                    $q->where('performed_by', $user->id);
                })
                ->count();
            $analystChartPayload = [
                'labels' => ['Tiket diterima', 'Selesai dianalisis', 'Belum dianalisis'],
                'values' => [
                    $analystTicketAssignedCount,
                    $analystTicketAnalyzedCount,
                    $analystTicketPendingAnalysisCount,
                ],
            ];
        }

        $showPicTicketStatsCard = $user instanceof User && $user->hasRole('pic');
        $picTicketTotalCount = null;
        $picTicketVerifiedCount = null;
        $picTicketRejectedCount = null;
        $picTicketOnProgressCount = null;
        $picChartPayload = null;
        if ($showPicTicketStatsCard) {
            $picTicketTotalCount = Ticket::query()->count();
            $picTicketVerifiedCount = Ticket::query()
                ->where('report_status', Ticket::REPORT_STATUS_VERIFIED)
                ->count();
            $picTicketRejectedCount = Ticket::query()
                ->where('report_status', Ticket::REPORT_STATUS_REJECTED)
                ->count();
            $picTicketOnProgressCount = Ticket::query()
                ->where('status', Ticket::STATUS_ON_PROGRESS)
                ->count();
            $picChartPayload = [
                'labels' => ['Diverifikasi', 'Ditolak', 'Dalam penanganan'],
                'values' => [
                    $picTicketVerifiedCount,
                    $picTicketRejectedCount,
                    $picTicketOnProgressCount,
                ],
            ];
        }

        $showCoordinatorTicketStatsCard = $user instanceof User && $user->hasRole('koordinator');
        $coordinatorTicketTotalCount = null;
        $coordinatorTicketOpenCount = null;
        $coordinatorTicketClosedCount = null;
        $coordinatorChartPayload = null;
        if ($showCoordinatorTicketStatsCard) {
            $coordinatorTicketTotalCount = Ticket::query()->count();
            $coordinatorTicketClosedCount = Ticket::query()
                ->where('status', Ticket::STATUS_CLOSED)
                ->count();
            $coordinatorTicketOpenCount = Ticket::query()
                ->where('status', '!=', Ticket::STATUS_CLOSED)
                ->count();
            $coordinatorChartPayload = [
                'labels' => ['Tiket dibuka', 'Tiket ditutup'],
                'values' => [$coordinatorTicketOpenCount, $coordinatorTicketClosedCount],
            ];
        }

        $showResponderTicketStatsCard = false;
        $responderTicketAssignedCount = null;
        $responderTicketCompletedCount = null;
        $responderTicketPendingCount = null;
        $responderChartPayload = null;
        if ($user instanceof User && $user->can('ticket.respond')) {
            $showResponderTicketStatsCard = true;
            $baseResponder = $this->responderDashboardTicketQuery($user);
            $responderTicketAssignedCount = (clone $baseResponder)->count();
            $responderTicketCompletedCount = (clone $baseResponder)
                ->where('sub_status', Ticket::SUB_STATUS_RESOLUTION)
                ->count();
            $responderTicketPendingCount = (clone $baseResponder)
                ->whereIn('sub_status', [Ticket::SUB_STATUS_ANALYSIS, Ticket::SUB_STATUS_RESPONSE])
                ->count();
            $responderChartPayload = [
                'labels' => ['Tiket diterima', 'Selesai ditangani', 'Belum ditangani'],
                'values' => [
                    $responderTicketAssignedCount,
                    $responderTicketCompletedCount,
                    $responderTicketPendingCount,
                ],
            ];
        }

        return view('livewire.pages.dashboard-page', [
            'stats' => $stats,
            'showChartSection' => $showChartSection,
            'canOpdChart' => $canOpdChart,
            'canTeamChart' => $canTeamChart,
            'chartPayload' => $chartPayload,
            'totalOrganizations' => $totalOrganizations,
            'opdChartRowCount' => $opdChartRowCount,
            'opdTopLimitOptions' => self::OPD_TOP_LIMIT_OPTIONS,
            'showAnalystTicketStatsCard' => $showAnalystTicketStatsCard,
            'analystTicketAssignedCount' => $analystTicketAssignedCount,
            'analystTicketAnalyzedCount' => $analystTicketAnalyzedCount,
            'analystTicketPendingAnalysisCount' => $analystTicketPendingAnalysisCount,
            'analystChartPayload' => $analystChartPayload,
            'showPicTicketStatsCard' => $showPicTicketStatsCard,
            'picTicketTotalCount' => $picTicketTotalCount,
            'picTicketVerifiedCount' => $picTicketVerifiedCount,
            'picTicketRejectedCount' => $picTicketRejectedCount,
            'picTicketOnProgressCount' => $picTicketOnProgressCount,
            'picChartPayload' => $picChartPayload,
            'showCoordinatorTicketStatsCard' => $showCoordinatorTicketStatsCard,
            'coordinatorTicketTotalCount' => $coordinatorTicketTotalCount,
            'coordinatorTicketOpenCount' => $coordinatorTicketOpenCount,
            'coordinatorTicketClosedCount' => $coordinatorTicketClosedCount,
            'coordinatorChartPayload' => $coordinatorChartPayload,
            'showResponderTicketStatsCard' => $showResponderTicketStatsCard,
            'responderTicketAssignedCount' => $responderTicketAssignedCount,
            'responderTicketCompletedCount' => $responderTicketCompletedCount,
            'responderTicketPendingCount' => $responderTicketPendingCount,
            'responderChartPayload' => $responderChartPayload,
        ]);
    }

    /**
     * Tiket analis yang masih relevan untuk kerja (bukan Closed / laporan ditolak), dengan penugasan aktif ke user.
     *
     * @return Builder<Ticket>
     */
    private function analystDashboardTicketQuery(User $user): Builder
    {
        return Ticket::query()
            ->where('status', '!=', Ticket::STATUS_CLOSED)
            ->where('status', '!=', Ticket::STATUS_REPORT_REJECTED)
            ->where('report_status', '!=', Ticket::REPORT_STATUS_REJECTED)
            ->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            });
    }

    /**
     * Selaras dengan antrean responder di daftar tiket: On Progress, penugasan aktif, sudah ada analisis, fase penanganan.
     *
     * @return Builder<Ticket>
     */
    private function responderDashboardTicketQuery(User $user): Builder
    {
        return Ticket::query()
            ->where('status', Ticket::STATUS_ON_PROGRESS)
            ->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->whereHas('analyses')
            ->whereIn('sub_status', [
                Ticket::SUB_STATUS_ANALYSIS,
                Ticket::SUB_STATUS_RESPONSE,
                Ticket::SUB_STATUS_RESOLUTION,
            ]);
    }
}
