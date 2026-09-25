<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ShortcutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserShortcutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.installed' => false,
        ]);

        Role::findOrCreate('Admin');
    }

    /** @test */
    public function catalog_contains_valid_routes()
    {
        $catalog = ShortcutService::getCatalog();
        $this->assertNotEmpty($catalog);

        foreach ($catalog as $key => $item) {
            $this->assertArrayHasKey('route', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertTrue(Route::has($item['route']), "Route {$item['route']} should exist in application.");
        }
    }

    /** @test */
    public function default_shortcuts_are_returned_when_user_has_no_preferences()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);

        $shortcuts = ShortcutService::getActiveShortcutsForUser($user);
        $this->assertNotEmpty($shortcuts);
        $this->assertLessThanOrEqual(6, count($shortcuts));
    }

    /** @test */
    public function user_can_customize_shortcuts_via_theme_endpoint()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);

        $customShortcuts = ['sales', 'reports.daily.sales', 'reports.sales.analysis'];

        $response = $this->postJson(route('user.theme.update'), [
            'key' => 'shortcuts',
            'value' => $customShortcuts,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals($customShortcuts, $user->theme['shortcuts']);

        $active = ShortcutService::getActiveShortcutsForUser($user);
        $activeKeys = array_column($active, 'key');

        $this->assertEquals($customShortcuts, $activeKeys);
    }

    /** @test */
    public function shortcuts_respect_user_permissions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $itemWithPerm = [
            'key' => 'audit.invoices',
            'route' => 'audit.invoices',
            'permission' => 'collections.audit',
        ];

        // Regular user without permission should not access
        $this->assertFalse(ShortcutService::canAccessShortcut($itemWithPerm, $user));

        // When permission is granted, user can access
        Permission::findOrCreate('collections.audit');
        $user->givePermissionTo('collections.audit');
        $this->assertTrue(ShortcutService::canAccessShortcut($itemWithPerm, $user));
    }

    /** @test */
    public function breadcrumb_view_renders_shortcuts_cleanly()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);

        session(['pos' => 'Ventas POS']);

        $view = $this->view('layouts.theme.breadcrumb');
        $view->assertSee('shortcuts-ribbon');
        $view->assertSee('shortcutsModal');
    }

    /** @test */
    public function system_bag_factory_index_endpoint_renders_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);

        $response = $this->get(route('system.bag_factory.index'));
        $response->assertStatus(200);
        $response->assertSee('JSBolsas - Control de Fábrica y Supervisión');
    }
}

