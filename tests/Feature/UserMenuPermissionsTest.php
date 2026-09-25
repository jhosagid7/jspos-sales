<?php

namespace Tests\Feature;

use App\Livewire\Settings\UserMenuPermissions;
use App\Models\User;
use App\Services\ShortcutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserMenuPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.installed' => false,
        ]);

        Role::findOrCreate('Super Admin');
        Role::findOrCreate('Admin');
        Role::findOrCreate('Cajero');
    }

    /** @test */
    public function only_super_admin_can_access_user_menu_permissions_page()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $cajero = User::factory()->create();
        $cajero->assignRole('Cajero');

        // Non-super-admin should be forbidden (403)
        $this->actingAs($cajero)
            ->get(route('settings.user_menus'))
            ->assertStatus(403);

        // Super admin can access successfully (200)
        $this->actingAs($superAdmin)
            ->get(route('settings.user_menus'))
            ->assertStatus(200);
    }

    /** @test */
    public function unconfigured_user_defaults_to_role_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Without customization, allowed menus is null
        $this->assertNull($user->theme['allowed_menus'] ?? null);

        // User can access shortcuts allowed by their role
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('welcome', $user));
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $user));
    }

    /** @test */
    public function super_admin_can_restrict_menus_for_specific_user()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Super Admin restricts menus: only allows 'sales'
        ShortcutService::setAllowedMenusForUser($user, ['sales']);
        $user->refresh();

        $this->assertEquals(['sales'], $user->theme['allowed_menus']);

        // 'sales' is allowed, but 'welcome' is blocked
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $user));
        $this->assertFalse(ShortcutService::isMenuAllowedForUser('welcome', $user));
    }

    /** @test */
    public function available_menus_in_modal_respects_user_overrides()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Initially, user has multiple available menus
        $allInitial = ShortcutService::getAvailableMenusForUser($user);
        $this->assertGreaterThan(1, count($allInitial));

        // After restricting to only 2 menus
        ShortcutService::setAllowedMenusForUser($user, ['sales', 'inventories']);
        $user->refresh();

        $filtered = ShortcutService::getAvailableMenusForUser($user);
        $this->assertCount(2, $filtered);
        $this->assertArrayHasKey('sales', $filtered);
        $this->assertArrayHasKey('inventories', $filtered);
        $this->assertArrayNotHasKey('welcome', $filtered);
    }

    /** @test */
    public function resetting_to_role_defaults_clears_user_overrides()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        ShortcutService::setAllowedMenusForUser($user, ['sales']);
        $user->refresh();
        $this->assertNotNull($user->theme['allowed_menus']);

        // Reset to null
        ShortcutService::setAllowedMenusForUser($user, null);
        $user->refresh();

        $this->assertNull($user->theme['allowed_menus'] ?? null);
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('welcome', $user));
    }

    /** @test */
    public function super_admin_is_never_restricted_by_menu_overrides()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        // Even if explicitly set empty
        ShortcutService::setAllowedMenusForUser($superAdmin, []);
        $superAdmin->refresh();

        // Super admin still has access to everything
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('welcome', $superAdmin));
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $superAdmin));
    }

    /** @test */
    public function livewire_component_can_toggle_and_save_user_permissions()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $employee = User::factory()->create(['name' => 'Empleado Ventas']);
        $employee->assignRole('Admin');

        $this->actingAs($superAdmin);

        Livewire::test(UserMenuPermissions::class)
            ->set('selectedUserId', $employee->id)
            ->call('selectAll')
            ->call('toggleMenu', 'welcome') // remove welcome
            ->call('save');

        $employee->refresh();
        $this->assertFalse(ShortcutService::isMenuAllowedForUser('welcome', $employee));
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $employee));
    }
}
