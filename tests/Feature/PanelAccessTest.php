<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear permission cache between tests
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_guest_is_redirected_from_admin_login_check(): void
    {
        $this->get('/admin/categories')->assertRedirect();
    }

    public function test_authenticated_user_can_access_admin_panel(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_categories_resource(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/categories');

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_items_resource(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/items');

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_transactions_resource(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/transactions');

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_shield_roles(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/shield/roles');

        $response->assertOk();
    }

    public function test_user_without_super_admin_cannot_access_shield_roles(): void
    {
        // Create a plain user without any role or permission
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/shield/roles');

        // Filament redirects unauthorized access (403 or redirect)
        $response->assertStatus(403);
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_authenticated_user_can_access_create_transaction_page(): void
    {
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin/transactions/create');

        $response->assertOk();
    }
}
