<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Sidebar extends Component
{
    /**
     * @var array<int, array{label: string, icon: string, route: string, permission: string|null}>
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
    }

    public function isActive(string $routeName): bool
    {
        if ($routeName === 'profile.edit') {
            return request()->routeIs(['profile', 'profile.edit', 'profile.*']);
        }

        return request()->routeIs($routeName) || request()->routeIs($routeName . '.*');
    }

    public function render()
    {
        return view('livewire.layout.sidebar');
    }
}
