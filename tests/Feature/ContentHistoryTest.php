<?php

namespace Tests\Feature;

use App\Models\ContentRevision;
use App\Models\HomeDoctor;
use App\Models\HomeText;
use App\Models\User;
use App\Support\RevisionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class ContentHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_access_history_or_rollback(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.history.index'))
            ->assertStatus(403);

        $this->actingAs($editor)
            ->get('/admin/history/some-batch-uuid')
            ->assertStatus(403);

        $this->actingAs($editor)
            ->post('/admin/history/some-batch-uuid/rollback')
            ->assertStatus(403);
    }

    public function test_admin_can_access_history_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.history.index'))
            ->assertStatus(200);
    }

    public function test_content_create_update_delete_generates_revisions(): void
    {
        $admin = User::factory()->admin()->create();

        $doctor = $this->actingAs($admin)->withoutMiddleware(ThrottleRequests::class);

        // 1. Created event
        $doc = HomeDoctor::create([
            'image' => 'dr-alpha.webp',
            'name' => 'Dr. Alpha',
            'title' => 'Spesialis Anak',
            'text' => 'Bio dokter.',
            'sort_order' => 1,
        ]);

        $createRev = ContentRevision::where('revisionable_type', HomeDoctor::class)
            ->where('revisionable_id', (string) $doc->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($createRev);
        $this->assertEquals('Dr. Alpha', $createRev->new_values['name']);

        // 2. Updated event
        $doc->update(['name' => 'Dr. Alpha Updated']);

        $updateRev = ContentRevision::where('revisionable_type', HomeDoctor::class)
            ->where('revisionable_id', (string) $doc->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($updateRev);
        $this->assertEquals('Dr. Alpha', $updateRev->old_values['name']);
        $this->assertEquals('Dr. Alpha Updated', $updateRev->new_values['name']);

        // 3. Deleted event
        $doc->delete();

        $deleteRev = ContentRevision::where('revisionable_type', HomeDoctor::class)
            ->where('revisionable_id', (string) $doc->id)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($deleteRev);
        $this->assertEquals('Dr. Alpha Updated', $deleteRev->old_values['name']);
    }

    public function test_no_revision_created_when_saved_without_real_changes(): void
    {
        $doc = HomeDoctor::create([
            'image' => 'dr-beta.webp',
            'name' => 'Dr. Beta',
            'title' => 'Spesialis Bedah',
            'text' => 'Bio bedah',
            'sort_order' => 2,
        ]);

        $initialCount = ContentRevision::where('revisionable_type', HomeDoctor::class)
            ->where('revisionable_id', (string) $doc->id)
            ->count();

        // Save again without changing anything
        $doc->update(['name' => 'Dr. Beta']);

        $afterCount = ContentRevision::where('revisionable_type', HomeDoctor::class)
            ->where('revisionable_id', (string) $doc->id)
            ->count();

        $this->assertEquals($initialCount, $afterCount, 'No revision should be created when attributes are unchanged.');
    }

    public function test_multiple_changes_in_one_request_share_the_same_batch_id(): void
    {
        $admin = User::factory()->admin()->create();

        $doc = HomeDoctor::create([
            'image' => 'dr-gamma.webp',
            'name' => 'Dr. Gamma',
            'title' => 'Spesialis Saraf',
            'text' => 'Bio saraf',
            'sort_order' => 3,
        ]);

        HomeText::create([
            'id' => 'hero_test_batch',
            'value' => 'Old Value',
        ]);

        // Clear previous revisions to test fresh request batching
        ContentRevision::truncate();

        $response = $this->actingAs($admin)
            ->put(route('home.update'), [
                'texts' => [
                    'hero_test_batch' => 'New Value via Controller',
                ],
                'doctors' => [
                    $doc->id => [
                        'name' => 'Dr. Gamma Updated',
                        'title' => 'Spesialis Saraf',
                        'text' => 'Bio baru',
                        'sort_order' => 3,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $revisions = ContentRevision::all();
        $this->assertGreaterThanOrEqual(2, $revisions->count());

        $batchIds = $revisions->pluck('batch_id')->unique();
        $this->assertCount(1, $batchIds, 'All revisions in the same request must share the same batch_id.');
    }

    public function test_rollback_update_restores_old_values_and_creates_new_batch(): void
    {
        $admin = User::factory()->admin()->create();

        $text = HomeText::create([
            'id' => 'test_rollback_text',
            'value' => 'Original Text',
        ]);

        // Perform update in a distinct batch
        $batchId = '11111111-1111-1111-1111-111111111111';
        app(RevisionContext::class)->setBatchId($batchId);
        $text->update(['value' => 'Modified Text']);

        $this->assertEquals('Modified Text', $text->fresh()->value);

        // Perform rollback via controller
        $response = $this->actingAs($admin)
            ->post(route('admin.history.rollback', $batchId));

        $response->assertRedirect(route('admin.history.index'));

        // Text should be restored to Original Text
        $this->assertEquals('Original Text', $text->fresh()->value);

        // A new rollback revision should be logged
        $rollbackRev = ContentRevision::where('meta->rolled_back_batch', $batchId)->first();
        $this->assertNotNull($rollbackRev);
        $this->assertNotEquals($batchId, $rollbackRev->batch_id);
    }

    public function test_rollback_create_deletes_record(): void
    {
        $admin = User::factory()->admin()->create();

        $batchId = '22222222-2222-2222-2222-222222222222';
        app(RevisionContext::class)->setBatchId($batchId);

        $doc = HomeDoctor::create([
            'image' => 'doc10.webp',
            'name' => 'Dr. To Delete Later',
            'title' => 'Dokter Umum',
            'text' => 'Bio 10',
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('home_doctors', ['id' => $doc->id, 'deleted_at' => null]);

        // Rollback creation
        $this->actingAs($admin)->post(route('admin.history.rollback', $batchId));

        $this->assertSoftDeleted('home_doctors', ['id' => $doc->id]);
    }

    public function test_rollback_delete_restores_record(): void
    {
        $admin = User::factory()->admin()->create();

        $doc = HomeDoctor::create([
            'image' => 'doc11.webp',
            'name' => 'Dr. To Restore',
            'title' => 'Dokter Gigi',
            'text' => 'Bio 11',
            'sort_order' => 11,
        ]);

        $batchId = '33333333-3333-3333-3333-333333333333';
        app(RevisionContext::class)->setBatchId($batchId);
        $doc->delete();

        $this->assertSoftDeleted('home_doctors', ['id' => $doc->id]);

        // Rollback deletion
        $this->actingAs($admin)->post(route('admin.history.rollback', $batchId));

        $this->assertDatabaseHas('home_doctors', ['id' => $doc->id, 'deleted_at' => null]);
    }

    public function test_rollback_with_conflict_requires_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $doc = HomeDoctor::create([
            'image' => 'doc12.webp',
            'name' => 'Initial Name',
            'title' => 'Dokter Spesialis',
            'text' => 'Bio 12',
            'sort_order' => 12,
        ]);

        // Batch 1: Change to Step 2
        $batch1 = '44444444-4444-4444-4444-444444444444';
        app(RevisionContext::class)->setBatchId($batch1);
        $doc->update(['name' => 'Step 2 Name']);

        // Batch 2: Change to Step 3 (conflict with batch 1's new_values)
        $batch2 = '55555555-5555-5555-5555-555555555555';
        app(RevisionContext::class)->setBatchId($batch2);
        $doc->update(['name' => 'Step 3 Name']);

        // Attempting to rollback batch 1 without confirmation should fail / redirect with error
        $response = $this->actingAs($admin)->post(route('admin.history.rollback', $batch1));
        $response->assertRedirect(route('admin.history.show', $batch1));
        $response->assertSessionHas('error');

        // The doctor should still be Step 3 Name
        $this->assertEquals('Step 3 Name', $doc->fresh()->name);

        // Attempting to rollback batch 1 WITH confirm_conflict = 1 should succeed
        $responseSuccess = $this->actingAs($admin)->post(route('admin.history.rollback', $batch1), [
            'confirm_conflict' => '1',
        ]);
        $responseSuccess->assertRedirect(route('admin.history.index'));
        $this->assertEquals('Initial Name', $doc->fresh()->name);
    }
}
