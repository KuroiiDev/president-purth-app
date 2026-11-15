<?php

namespace Tests\Feature;

use App\Models\HomeDoctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleted_doctor_hidden_from_public_page(): void
    {
        $this->seed();

        $doc = HomeDoctor::create([
            'image' => 'doc_visible.webp',
            'name' => 'Dr. Visible Test',
            'title' => 'Dokter Anak',
            'text' => 'Bio anak',
            'sort_order' => 99,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('Dr. Visible Test');

        // Soft delete the doctor
        $doc->delete();

        $responseAfter = $this->get(route('home'));
        $responseAfter->assertDontSee('Dr. Visible Test');
    }

    public function test_admin_and_editor_can_view_archive(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)
            ->get(route('admin.archive.index'))
            ->assertStatus(200);

        $this->actingAs($editor)
            ->get(route('admin.archive.index'))
            ->assertStatus(200);
    }

    public function test_admin_and_editor_can_restore_archived_item(): void
    {
        $editor = User::factory()->editor()->create();

        $doc = HomeDoctor::create([
            'image' => 'doc_trash.webp',
            'name' => 'Dr. Trashed Doctor',
            'title' => 'Dokter Umum',
            'text' => 'Bio umum',
            'sort_order' => 1,
        ]);
        $doc->delete();

        $this->assertSoftDeleted('home_doctors', ['id' => $doc->id]);

        $response = $this->actingAs($editor)
            ->post(route('admin.archive.restore', ['type' => 'doctors', 'id' => $doc->id]));

        $response->assertRedirect(route('admin.archive.index', ['tab' => 'doctors']));
        $this->assertDatabaseHas('home_doctors', ['id' => $doc->id, 'deleted_at' => null]);
    }

    public function test_force_delete_is_restricted_to_admin(): void
    {
        $editor = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();

        $doc = HomeDoctor::create([
            'image' => 'doc_force.webp',
            'name' => 'Dr. Force Delete Target',
            'title' => 'Dokter Bedah',
            'text' => 'Bio bedah',
            'sort_order' => 2,
        ]);
        $doc->delete();

        // Editor attempts force delete -> 403
        $this->actingAs($editor)
            ->delete(route('admin.archive.force-delete', ['type' => 'doctors', 'id' => $doc->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('home_doctors', ['id' => $doc->id]);

        // Admin attempts force delete -> 302 and record is permanently deleted
        $this->actingAs($admin)
            ->delete(route('admin.archive.force-delete', ['type' => 'doctors', 'id' => $doc->id]))
            ->assertRedirect(route('admin.archive.index', ['tab' => 'doctors']));

        $this->assertDatabaseMissing('home_doctors', ['id' => $doc->id]);
    }

    public function test_invalid_archive_type_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/archive/malicious_type/1/restore')
            ->assertStatus(404);

        $this->actingAs($admin)
            ->delete('/admin/archive/users/1')
            ->assertStatus(404);
    }
}
