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
    public function super_admin_without_overrides_has_full_access_and_with_overrides_respects_custom_menus_safely()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        // Without overrides, Super Admin has access to everything
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('welcome', $superAdmin));
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $superAdmin));
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('settings.user_menus', $superAdmin));

        // When custom overrides are configured (e.g. user Yuliana who has Super Admin role in production DB)
        ShortcutService::setAllowedMenusForUser($superAdmin, ['sales']);
        $superAdmin->refresh();

        // Allowed menu is accessible
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('sales', $superAdmin));
        // Unallowed menu is hidden
        $this->assertFalse(ShortcutService::isMenuAllowedForUser('welcome', $superAdmin));
        // Safety: management panel remains accessible for Super Admin
        $this->assertTrue(ShortcutService::isMenuAllowedForUser('settings.user_menus', $superAdmin));
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

    /** @test */
    public function sidebar_hides_dashboard_and_disallowed_menus_for_user_like_yuliana()
    {
        $yuliana = User::factory()->create(['name' => 'YULIANA PADILLA']);
        $yuliana->assignRole('Admin');

        // Initial state: unconfigured -> shows dashboard
        $view = $this->actingAs($yuliana)->view('layouts.theme.sidebar');
        $view->assertSee('DASHBOARD');
        $view->assertSee('Ventas (POS)');

        // Super Admin disables DASHBOARD and leaves only 'sales'
        ShortcutService::setAllowedMenusForUser($yuliana, ['sales']);
        $yuliana->refresh();

        // Render sidebar again
        $viewRestricted = $this->actingAs($yuliana)->view('layouts.theme.sidebar');
        $viewRestricted->assertDontSee('DASHBOARD');
        $viewRestricted->assertSee('Ventas (POS)');
        $viewRestricted->assertDontSee('Lista de Precios');
        $viewRestricted->assertDontSee('INVENTARIO Y PRODUCCIÓN');
        $viewRestricted->assertDontSee('FINANZAS Y AUDITORÍA');
    }

    /** @test */
    public function user_with_welcome_disallowed_is_redirected_from_welcome_route()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Restrict to only 'sales'
        ShortcutService::setAllowedMenusForUser($user, ['sales']);
        $user->refresh();

        // Visiting welcome redirects to their first available route ('sales')
        $this->actingAs($user)
            ->get(route('welcome'))
            ->assertRedirect(route('sales'));
    }

    /** @test */
    public function role_admin_menus_can_be_enabled_and_disabled()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Allow only products and sales
        ShortcutService::setAllowedMenusForUser($admin, ['products', 'sales']);
        $admin->refresh();

        $view = $this->actingAs($admin)->view('layouts.theme.sidebar');
        $view->assertSee('Ventas (POS)');
        $view->assertSee('Listado Maestro');
        $view->assertDontSee('DASHBOARD');
        $view->assertDontSee('FINANZAS Y AUDITORÍA');
        $view->assertDontSee('CENTRO DE REPORTES');
    }

    /** @test */
    public function role_supervisor_menus_can_be_enabled_and_disabled()
    {
        Role::findOrCreate('Supervisor');
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        // Only allowed reports.daily.sales
        ShortcutService::setAllowedMenusForUser($supervisor, ['reports.daily.sales']);
        $supervisor->refresh();

        $view = $this->actingAs($supervisor)->view('layouts.theme.sidebar');
        $view->assertSee('Ventas Diarias');
        $view->assertDontSee('DASHBOARD');
        $view->assertDontSee('Ventas (POS)');
        $view->assertDontSee('INVENTARIO Y PRODUCCIÓN');
    }

    /** @test */
    public function role_cajero_menus_can_be_enabled_and_disabled()
    {
        $cajero = User::factory()->create();
        $cajero->assignRole('Cajero');

        // Cajero with only cash-register.close and sales
        ShortcutService::setAllowedMenusForUser($cajero, ['sales', 'cash-register.close']);
        $cajero->refresh();

        $view = $this->actingAs($cajero)->view('layouts.theme.sidebar');
        $view->assertSee('Ventas (POS)');
        $view->assertSee('Cerrar Caja');
        $view->assertDontSee('DASHBOARD');
        $view->assertDontSee('Historial Arqueos');
        $view->assertDontSee('INVENTARIO Y PRODUCCIÓN');
    }

    /** @test */
    public function user_with_completely_empty_allowed_menus_hides_all_sections_in_sidebar()
    {
        $user = User::factory()->create(['name' => 'EMPTY USER']);
        $user->assignRole('Admin');

        // Empty allowed_menus (same as Yuliana in DB: allowed_menus = [])
        ShortcutService::setAllowedMenusForUser($user, []);
        $user->refresh();

        $view = $this->actingAs($user)->view('layouts.theme.sidebar');
        $view->assertDontSee('DASHBOARD');
        $view->assertDontSee('GESTIÓN COMERCIAL');
        $view->assertDontSee('LOGÍSTICA Y DESPACHO');
        $view->assertDontSee('INVENTARIO Y PRODUCCIÓN');
        $view->assertDontSee('FINANZAS Y AUDITORÍA');
        $view->assertDontSee('REGISTROS MAESTROS');
        $view->assertDontSee('CENTRO DE REPORTES');
    }
}
