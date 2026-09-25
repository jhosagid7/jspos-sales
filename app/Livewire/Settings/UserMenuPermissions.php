<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\ShortcutService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UserMenuPermissions extends Component
{
    public $selectedUserId;
    public $search = '';
    public $allowedMenus = [];
    public $hasCustomConfig = false;

    public function mount()
    {
        abort_unless(Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->hasRole('Super Admin')), 403, 'Acceso restringido a Super Administrador.');

        $firstUser = User::where('status', 'ACTIVE')->orderBy('name')->first();
        if ($firstUser) {
            $this->selectedUserId = $firstUser->id;
            $this->loadUserPermissions();
        }
    }

    public function updatedSelectedUserId()
    {
        $this->loadUserPermissions();
    }

    public function loadUserPermissions()
    {
        $user = User::find($this->selectedUserId);
        if (!$user) {
            $this->allowedMenus = [];
            $this->hasCustomConfig = false;
            return;
        }

        $theme = $user->theme;
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }
        $theme = is_array($theme) ? $theme : [];

        if (isset($theme['allowed_menus']) && is_array($theme['allowed_menus'])) {
            $this->allowedMenus = $theme['allowed_menus'];
            $this->hasCustomConfig = true;
        } else {
            // Cargar los menús que su rol le permite por defecto
            $this->allowedMenus = ShortcutService::getRoleDefaultMenuKeys($user);
            $this->hasCustomConfig = false;
        }
    }

    public function toggleMenu($key)
    {
        if (in_array($key, $this->allowedMenus)) {
            $this->allowedMenus = array_values(array_diff($this->allowedMenus, [$key]));
        } else {
            $this->allowedMenus[] = $key;
        }
    }

    public function selectAll()
    {
        $catalog = ShortcutService::getCatalog();
        $this->allowedMenus = array_keys($catalog);
    }

    public function deselectAll()
    {
        $this->allowedMenus = [];
    }

    public function selectCategory($category)
    {
        $catalog = ShortcutService::getCatalog();
        foreach ($catalog as $key => $item) {
            if (($item['category'] ?? 'General') === $category && !in_array($key, $this->allowedMenus)) {
                $this->allowedMenus[] = $key;
            }
        }
    }

    public function deselectCategory($category)
    {
        $catalog = ShortcutService::getCatalog();
        $keysToRemove = [];
        foreach ($catalog as $key => $item) {
            if (($item['category'] ?? 'General') === $category) {
                $keysToRemove[] = $key;
            }
        }
        $this->allowedMenus = array_values(array_diff($this->allowedMenus, $keysToRemove));
    }

    public function resetToRoleDefaults()
    {
        $user = User::find($this->selectedUserId);
        if ($user) {
            ShortcutService::setAllowedMenusForUser($user, null);
            $this->loadUserPermissions();
            $this->dispatch('noty', msg: "Permisos de '{$user->name}' restablecidos a los valores por defecto de su rol.");
        }
    }

    public function save()
    {
        $user = User::find($this->selectedUserId);
        if (!$user) {
            $this->dispatch('noty-error', msg: 'Usuario no encontrado.');
            return;
        }

        ShortcutService::setAllowedMenusForUser($user, $this->allowedMenus);
        $this->hasCustomConfig = true;
        $this->dispatch('noty', msg: "Permisos de menús para '{$user->name}' guardados exitosamente.");
    }

    public function render()
    {
        $users = User::where('status', 'ACTIVE')->orderBy('name')->get();
        $selectedUser = User::find($this->selectedUserId);

        $catalog = ShortcutService::getCatalog();
        $filteredCatalog = [];
        $searchClean = mb_strtolower(trim($this->search));

        foreach ($catalog as $key => $item) {
            if ($searchClean !== '') {
                $label = mb_strtolower($item['label'] ?? '');
                $category = mb_strtolower($item['category'] ?? '');
                $route = mb_strtolower($item['route'] ?? '');
                if (!str_contains($label, $searchClean) && !str_contains($category, $searchClean) && !str_contains($route, $searchClean)) {
                    continue;
                }
            }
            $cat = $item['category'] ?? 'General';
            $filteredCatalog[$cat][] = $item;
        }

        return view('livewire.settings.user-menu-permissions', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'groupedCatalog' => $filteredCatalog,
            'totalCatalogCount' => count($catalog),
        ]);
    }
}
