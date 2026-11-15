<?php

namespace Tests\Feature;

use App\Models\HomeDoctor;
use App\Models\HomeText;
use App\Models\User;
use App\Services\RollbackService;
use App\Support\HomeContentCache;
use App\Support\RevisionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_first_request_populates_cache_and_second_request_executes_no_content_queries(): void
    {
        $this->seed();

        // 1st request - Cache miss, populates cache
        $response1 = $this->get(route('home'));
        $response1->assertStatus(200);

        $this->assertTrue(Cache::has(HomeContentCache::CACHE_KEY));

        // 2nd request - Cache hit, should not execute content table queries
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response2 = $this->get(route('home'));
        $response2->assertStatus(200);

        $queries = DB::getQueryLog();
        $contentQueries = array_filter($queries, function ($q) {
            $sql = strtolower($q['query']);

            return str_contains($sql, 'home_texts')
                || str_contains($sql, 'home_cards')
                || str_contains($sql, 'home_doctors')
                || str_contains($sql, 'home_testimonies');
        });

        $this->assertEmpty($contentQueries, 'Second request must not execute any content queries because of cache hit.');
    }

    public function test_updating_text_busts_cache(): void
    {
        $this->seed();

        $this->get(route('home'))->assertSee('PRESIDENT UNIVERSITY HOSPITAL');

        // Update text
        HomeText::find('hero_title')->update(['value' => 'RUMAH SAKIT UNIVERSITAS PRESIDEN']);

        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache should be busted on text update.');

        $this->get(route('home'))->assertSee('RUMAH SAKIT UNIVERSITAS PRESIDEN');
    }

    public function test_modifying_doctor_busts_cache(): void
    {
        $this->seed();

        $this->get(route('home'));

        // Create new doctor
        $doc = HomeDoctor::create([
            'image' => 'dr-new.webp',
            'name' => 'Dr. Dokter Baru Cache',
            'title' => 'Spesialis Jantung',
            'text' => 'Bio baru',
            'sort_order' => 50,
        ]);

        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache should be busted on doctor create.');
        $this->get(route('home'))->assertSee('Dr. Dokter Baru Cache');

        // Update doctor
        $doc->update(['name' => 'Dr. Dokter Ganti Nama']);
        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache should be busted on doctor update.');
        $this->get(route('home'))->assertSee('Dr. Dokter Ganti Nama');
    }

    public function test_deleting_and_restoring_busts_cache(): void
    {
        $this->seed();

        $doc = HomeDoctor::create([
            'image' => 'dr-del.webp',
            'name' => 'Dr. Calon Hapus',
            'title' => 'Dokter Umum',
            'text' => 'Bio umum',
            'sort_order' => 51,
        ]);

        $this->get(route('home'))->assertSee('Dr. Calon Hapus');

        // Soft delete
        $doc->delete();
        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache should be busted on soft delete.');
        $this->get(route('home'))->assertDontSee('Dr. Calon Hapus');

        // Restore
        $doc->restore();
        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache should be busted on restore.');
        $this->get(route('home'))->assertSee('Dr. Calon Hapus');
    }

    public function test_cache_busts_even_when_revisions_are_disabled(): void
    {
        $this->seed();

        $this->get(route('home'));
        $this->assertTrue(Cache::has(HomeContentCache::CACHE_KEY));

        // Update inside withoutRecording
        app(RevisionContext::class)->withoutRecording(function () {
            HomeText::find('hero_title')->update(['value' => 'Title Without Revisions']);
        });

        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Cache must be busted even when withoutRecording is active.');
        $this->get(route('home'))->assertSee('Title Without Revisions');
    }

    public function test_rollback_busts_cache(): void
    {
        $this->seed();

        $admin = User::factory()->admin()->create();

        $doc = HomeDoctor::create([
            'image' => 'dr-rb.webp',
            'name' => 'Dr. Nama Asli',
            'title' => 'Dokter',
            'text' => 'Bio',
            'sort_order' => 60,
        ]);

        // Request populates cache
        $this->get(route('home'))->assertSee('Dr. Nama Asli');

        // Update in a tracked batch
        $batchId = '99999999-9999-9999-9999-999999999999';
        app(RevisionContext::class)->setBatchId($batchId);
        $doc->update(['name' => 'Dr. Nama Diubah']);

        // Cache reflects updated name
        $this->get(route('home'))->assertSee('Dr. Nama Diubah');
        $this->assertTrue(Cache::has(HomeContentCache::CACHE_KEY));

        // Perform rollback
        app(RollbackService::class)->rollback($batchId);

        $this->assertFalse(Cache::has(HomeContentCache::CACHE_KEY), 'Rollback must bust cache.');
        $this->get(route('home'))->assertSee('Dr. Nama Asli');
    }
}
