<?php

namespace Tests\Feature;

use App\Models\HomeDoctor;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('home_images');
        Storage::fake('home_archive');
    }

    public function test_valid_image_upload_produces_webp(): void
    {
        $uploader = app(ImageUploader::class);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $filename = $uploader->store($file, 'profile', 'dr-john');

        $this->assertStringEndsWith('.webp', $filename);
        $this->assertStringStartsWith('dr-john-', $filename);
        Storage::disk('home_images')->assertExists('profile/'.$filename);
    }

    public function test_large_image_is_resized_to_max_1200_width(): void
    {
        $uploader = app(ImageUploader::class);

        $file = UploadedFile::fake()->image('large.png', 3000, 2000);

        $filename = $uploader->store($file, 'profile', 'big-photo');

        Storage::disk('home_images')->assertExists('profile/'.$filename);

        // Re-read the stored file and check dimensions
        $path = Storage::disk('home_images')->path('profile/'.$filename);
        $info = getimagesize($path);
        $this->assertLessThanOrEqual(1200, $info[0], 'Width should be max 1200px');
    }

    public function test_small_image_is_not_upscaled(): void
    {
        $uploader = app(ImageUploader::class);

        $file = UploadedFile::fake()->image('tiny.png', 400, 300);

        $filename = $uploader->store($file, 'profile', 'small-pic');

        $path = Storage::disk('home_images')->path('profile/'.$filename);
        $info = getimagesize($path);
        $this->assertEquals(400, $info[0], 'Width should remain 400px (no upscale)');
    }

    public function test_invalid_file_content_is_rejected(): void
    {
        $uploader = app(ImageUploader::class);

        // Create a text file disguised with .jpg extension
        $file = UploadedFile::fake()->create('malicious.jpg', 100, 'text/plain');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File bukan gambar yang valid');

        $uploader->store($file, 'profile', 'evil');
    }

    public function test_replacing_image_archives_old_file(): void
    {
        $uploader = app(ImageUploader::class);

        // Store an initial image
        $file1 = UploadedFile::fake()->image('first.jpg', 800, 600);
        $filename1 = $uploader->store($file1, 'profile', 'dr-anna');

        Storage::disk('home_images')->assertExists('profile/'.$filename1);

        // Archive the old image (simulating a replacement)
        $uploader->archive($filename1, 'profile');

        // Old file should be gone from public
        Storage::disk('home_images')->assertMissing('profile/'.$filename1);

        // But exists in archive
        $archivedFiles = Storage::disk('home_archive')->files('profile');
        $this->assertNotEmpty($archivedFiles, 'Archived file should exist in archive disk');
    }

    public function test_archive_ignores_external_urls(): void
    {
        $uploader = app(ImageUploader::class);

        // Should not throw or do anything
        $uploader->archive('https://example.com/photo.jpg', 'profile');

        $archivedFiles = Storage::disk('home_archive')->files('profile');
        $this->assertEmpty($archivedFiles);
    }

    public function test_restore_from_archive(): void
    {
        $uploader = app(ImageUploader::class);

        // Store, archive, then restore
        $file = UploadedFile::fake()->image('restore-me.png', 500, 400);
        $filename = $uploader->store($file, 'profile', 'restore-test');

        $uploader->archive($filename, 'profile');
        Storage::disk('home_images')->assertMissing('profile/'.$filename);

        $result = $uploader->restore($filename, 'profile');

        $this->assertTrue($result);
        Storage::disk('home_images')->assertExists('profile/'.$filename);
    }

    public function test_restore_returns_false_when_file_not_in_archive(): void
    {
        $uploader = app(ImageUploader::class);

        $result = $uploader->restore('nonexistent.webp', 'profile');

        $this->assertFalse($result);
    }

    public function test_svg_is_rejected_by_controller_validation(): void
    {
        $this->seed();

        $admin = User::factory()->admin()->create();
        $doctor = HomeDoctor::factory()->create();

        $svgFile = UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml');

        $response = $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => $doctor->name,
            'title' => $doctor->title,
            'image' => $svgFile,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_doctor_controller_upload_uses_image_uploader(): void
    {
        $this->seed();

        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->image('new-doctor.jpg', 800, 600);

        $response = $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'Dr. Test Upload',
            'title' => 'Spesialis Tes',
            'text' => 'Bio test.',
            'image' => $file,
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.doctors.index'));

        $doctor = HomeDoctor::where('name', 'Dr. Test Upload')->first();
        $this->assertNotNull($doctor);
        $this->assertStringEndsWith('.webp', $doctor->image);
        Storage::disk('home_images')->assertExists('profile/'.$doctor->image);
    }

    public function test_purge_archive_removes_old_files(): void
    {
        $uploader = app(ImageUploader::class);

        Carbon::setTestNow(now());
        Storage::disk('home_archive')->put('profile/old_file.webp', 'old content');

        // Advance time by 100 days
        Carbon::setTestNow(now()->addDays(100));

        $count = $uploader->purgeArchive(90);

        $this->assertEquals(1, $count);
        Storage::disk('home_archive')->assertMissing('profile/old_file.webp');

        Carbon::setTestNow();
    }
}
