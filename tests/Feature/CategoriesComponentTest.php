<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_categories_component()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('categories.index');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Categories::class)
            ->assertStatus(200);
    }

    public function test_can_create_category()
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['categories.index', 'categories.create']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Categories::class)
            ->set('category.name', 'Nueva Categoria Test')
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Nueva Categoria Test'
        ]);
    }

    public function test_can_edit_category()
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['categories.index', 'categories.edit']);

        $category = Category::create(['name' => 'Categoria Original']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Categories::class)
            ->call('Edit', $category->id)
            ->set('category.name', 'Categoria Modificada')
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Categoria Modificada'
        ]);
    }
}
