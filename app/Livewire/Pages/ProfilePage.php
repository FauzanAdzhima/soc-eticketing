<?php

namespace App\Livewire\Pages;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class ProfilePage extends Component
{
    public bool $showEditProfileModal = false;
    public bool $showChangePasswordModal = false;

    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->syncProfileForm();
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function openEditProfileModal(): void
    {
        $this->syncProfileForm();
        $this->resetValidation();
        $this->showEditProfileModal = true;
    }

    public function closeEditProfileModal(): void
    {
        $this->showEditProfileModal = false;
    }

    public function openChangePasswordModal(): void
    {
        $this->resetValidation();
        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');
        $this->showChangePasswordModal = true;
    }

    public function closeChangePasswordModal(): void
    {
        $this->showChangePasswordModal = false;
    }

    public function saveProfile(): void
    {
        $user = $this->user;

        if (!$user) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        $this->syncProfileForm();
        $this->showEditProfileModal = false;
        session()->flash('profile_success', 'Profil berhasil diperbarui.');
    }

    public function savePassword(): void
    {
        $user = $this->user;

        if (!$user) {
            return;
        }

        $validated = $this->validate([
            'currentPassword' => ['required', 'string', 'current_password:web'],
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['newPassword']),
        ]);

        $this->showChangePasswordModal = false;
        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');
        session()->flash('password_success', 'Password berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.pages.profile-page');
    }

    private function syncProfileForm(): void
    {
        $this->name = (string) ($this->user?->name ?? '');
        $this->email = (string) ($this->user?->email ?? '');
    }
}
