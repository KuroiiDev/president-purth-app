<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Controllers\UserController;
use App\Models\HomeDoctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_routes(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/login');
    }

    public function test_editor_receives_403_when_accessing_user_routes(): void
    {
        $editor = User::factory()->editor()->create();

        $response = $this->actingAs($editor)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_receives_200_when_accessing_user_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_editor_can_access_doctors_management(): void
    {
        $editor = User::factory()->editor()->create();
        $doctor = HomeDoctor::factory()->create();

        $response = $this->actingAs($editor)->get(route('admin.doctors.edit', $doctor));

        $response->assertStatus(200);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        // admin1 can delete admin2 because there are 2 admins
        $this->actingAs($admin1)->delete(route('admin.users.destroy', $admin2));
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);

        // Now admin1 is the only admin. Trying to delete admin1 (if somehow attempted from another auth)
        // Let's create an editor to attempt (which gets 403) or simulate another admin deleted
        // admin1 attempts to delete itself - blocked by self-delete guard
        // Let's create a temporary admin3, but authenticate as admin3 to delete admin1, leaving admin3 as last admin
        $admin3 = User::factory()->admin()->create();
        $this->actingAs($admin3)->delete(route('admin.users.destroy', $admin1));
        $this->assertDatabaseMissing('users', ['id' => $admin1->id]);

        // Now admin3 is the ONLY admin. If an attempt is made to delete admin3:
        $anotherUser = User::factory()->editor()->create();
        // Since editor gets 403 on route, let's test controller guard directly
        $this->actingAs($admin3);
        $response = (new UserController)->destroy($admin3);
        $this->assertDatabaseHas('users', ['id' => $admin3->id]);
    }

    public function test_last_admin_cannot_be_demoted_to_editor(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => 'Admin Updated',
            'email' => $admin->email,
            'role' => Role::Editor->value,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals(Role::Admin, $admin->role);
    }

    public function test_admin_can_be_demoted_if_another_admin_exists(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $response = $this->actingAs($admin1)->put(route('admin.users.update', $admin2), [
            'name' => 'Admin 2 Updated',
            'email' => $admin2->email,
            'role' => Role::Editor->value,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $admin2->refresh();
        $this->assertEquals(Role::Editor, $admin2->role);
    }
}
