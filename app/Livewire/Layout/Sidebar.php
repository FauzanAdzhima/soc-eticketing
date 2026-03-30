<?php

namespace App\Livewire\Layout;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Sidebar extends Component
{
    /**
     * @var array<int, array{label: string, icon: string, route: string, group: string, permission: string|null, route_query?: array<string, string>}>
     */
    public array $menus = [];

    public function mount(): void
    {
        $items = (array) config('sidebar.items', []);
        $user = Auth::user();

        $this->menus = array_values(array_filter($items, function (array $item) use ($user): bool {
            $permission = $item['permission'] ?? null;

            if (blank($permission)) {
                return true;
            }

            return $user ? Gate::forUser($user)->allows($permission) : false;
        }));

        $this->menus = array_map(function (array $item): array {
            $item['group'] = (string) ($item['group'] ?? 'core');

            return $item;
        }, $this->menus);

        $this->menus = array_values(array_filter($this->menus, function (array $item) use ($user): bool {
            if (! $user instanceof User) {
                return true;
            }

            $isDefaultTicketIndex = ($item['route'] ?? '') === 'tickets.index' && empty($item['route_query'] ?? []);
            if ($isDefaultTicketIndex && $user->seesOnlyAnalystTicketListInNavigation()) {
                return false;
            }

            if ($isDefaultTicketIndex && $user->seesOnlyResponderTicketListInNavigation()) {
                return false;
            }

            return true;
        }));

        $this->menus = $this->sortMenusByOperationalHierarchy($this->menus);
    }

    /**
     * Multi-role: urutan PIC (Daftar Tiket) → Analis → Responder, lalu admin. Item dalam grup tetap urutan config.
     *
     * @param  array<int, array<string, mixed>>  $menus
     * @return array<int, array<string, mixed>>
     */
    private function sortMenusByOperationalHierarchy(array $menus): array
    {
        $groupRank = [
            'core' => 0,
            'ticket_ops' => 10,
            'analyst_work' => 20,
            'responder_work' => 30,
            'admin_registry' => 100,
        ];

        $indexed = [];
        foreach ($menus as $i => $item) {
            $indexed[] = ['i' => $i, 'item' => $item];
        }

        usort($indexed, function (array $x, array $y) use ($groupRank): int {
            $gx = $groupRank[$x['item']['group']] ?? 50;
            $gy = $groupRank[$y['item']['group']] ?? 50;
            if ($gx !== $gy) {
                return $gx <=> $gy;
            }

            return $x['i'] <=> $y['i'];
        });

        return array_map(fn (array $row) => $row['item'], $indexed);
    }

    /**
     * @param  array{label: string, icon: string, route: string, group: string, permission: string|null, route_query?: array<string, string>}  $menu
     */
    public function isActiveForMenu(array $menu): bool
    {
        $routeName = $menu['route'];
        $routeQuery = $menu['route_query'] ?? [];

        if ($routeName === 'profile.edit') {
            return request()->routeIs(['profile', 'profile.edit', 'profile.*']);
        }

        if ($routeName === 'tickets.index' && ($routeQuery['scope'] ?? null) === 'analyst') {
            return (request()->routeIs('tickets.index') && $this->isAnalystScopedTicketsIndex())
                || request()->routeIs('tickets.analysis');
        }

        if ($routeName === 'tickets.index' && ($routeQuery['scope'] ?? null) === 'responder') {
            return (request()->routeIs('tickets.index') && $this->isResponderScopedTicketsIndex())
                || request()->routeIs('tickets.respond');
        }

        if ($routeName === 'tickets.index' && $routeQuery === []) {
            // Jangan sertakan tickets.analysis di sini — halaman analisis termasuk alur "Analisis Tiket",
            // bukan daftar tiket PIC; jika keduanya true, kedua item sidebar ikut aktif.
            return request()->routeIs('tickets.index') && ! $this->isAnalystScopedTicketsIndex()
                && ! $this->isResponderScopedTicketsIndex();
        }

        return request()->routeIs($routeName) || request()->routeIs($routeName . '.*');
    }

    private function isAnalystScopedTicketsIndex(): bool
    {
        if (request()->query('scope') === 'analyst') {
            return true;
        }

        $user = Auth::user();

        return $user instanceof User
            && request()->routeIs('tickets.index')
            && $user->seesOnlyAnalystTicketListInNavigation();
    }

    private function isResponderScopedTicketsIndex(): bool
    {
        if (request()->query('scope') === 'responder') {
            return true;
        }

        $user = Auth::user();

        return $user instanceof User
            && request()->routeIs('tickets.index')
            && $user->seesOnlyResponderTicketListInNavigation();
    }

    public function render()
    {
        return view('livewire.layout.sidebar');
    }
}
