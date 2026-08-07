<?php

namespace Tests\Feature;

use App\Support\ImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStoreTest extends TestCase
{
    private function withCloudinary(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.key' => '232257738363373',
            'services.cloudinary.secret' => 'test-secret',
            'services.cloudinary.folder' => 'khandani-legacy',
        ]);
    }

    private function withoutCloudinary(): void
    {
        config([
            'services.cloudinary.cloud_name' => null,
            'services.cloudinary.key' => null,
            'services.cloudinary.secret' => null,
        ]);
    }

    public function test_it_falls_back_to_local_storage_when_not_configured(): void
    {
        $this->withoutCloudinary();
        Storage::fake('public');
        Http::fake();

        $stored = ImageStore::put(UploadedFile::fake()->image('gran.jpg'), 'profile-photos');

        $this->assertStringStartsWith('profile-photos/', $stored);
        Storage::disk('public')->assertExists($stored);
        Http::assertNothingSent();
    }

    public function test_it_uploads_to_cloudinary_and_stores_the_returned_address(): void
    {
        $this->withCloudinary();
        Http::fake([
            'api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/test-cloud/image/upload/v1712345678/khandani-legacy/profile-photos/abc123.jpg',
                'public_id' => 'khandani-legacy/profile-photos/abc123',
            ]),
        ]);

        $stored = ImageStore::put(UploadedFile::fake()->image('gran.jpg'), 'profile-photos');

        $this->assertSame(
            'https://res.cloudinary.com/test-cloud/image/upload/v1712345678/khandani-legacy/profile-photos/abc123.jpg',
            $stored
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), '/test-cloud/image/upload'));
    }

    public function test_the_upload_is_signed_and_the_secret_never_leaves_the_server(): void
    {
        $this->withCloudinary();
        Http::fake(['api.cloudinary.com/*' => Http::response(['secure_url' => 'https://res.cloudinary.com/x/image/upload/v1/a.jpg'])]);

        ImageStore::put(UploadedFile::fake()->image('gran.jpg'), 'profile-photos');

        Http::assertSent(function ($request) {
            $body = $request->body();

            return str_contains($body, 'signature')
                && str_contains($body, 'api_key')
                && str_contains($body, '232257738363373')
                && ! str_contains($body, 'test-secret');
        });
    }

    public function test_a_rejected_upload_raises_the_reason_rather_than_saving_nothing(): void
    {
        $this->withCloudinary();
        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid API key']], 401),
        ]);

        $this->expectExceptionMessage('Invalid API key');

        ImageStore::put(UploadedFile::fake()->image('gran.jpg'), 'profile-photos');
    }

    public function test_urls_are_returned_untouched_and_paths_are_resolved(): void
    {
        Storage::fake('public');

        $remote = 'https://res.cloudinary.com/test-cloud/image/upload/v1/a.jpg';
        $this->assertSame($remote, ImageStore::url($remote));

        // Resolved through the public disk, so it depends on that disk's own
        // URL setting rather than being absolute in every environment.
        $local = ImageStore::url('profile-photos/abc.jpg');
        $this->assertStringContainsString('storage/profile-photos/abc.jpg', $local);

        $this->assertNull(ImageStore::url(null));
        $this->assertNull(ImageStore::url(''));
    }

    public function test_deleting_a_local_path_removes_the_file(): void
    {
        $this->withoutCloudinary();
        Storage::fake('public');

        $stored = ImageStore::put(UploadedFile::fake()->image('old.jpg'), 'profile-photos');
        Storage::disk('public')->assertExists($stored);

        ImageStore::delete($stored);

        Storage::disk('public')->assertMissing($stored);
    }

    public function test_deleting_a_cloudinary_url_asks_cloudinary_for_the_right_public_id(): void
    {
        $this->withCloudinary();
        Http::fake(['api.cloudinary.com/*' => Http::response(['result' => 'ok'])]);

        ImageStore::delete('https://res.cloudinary.com/test-cloud/image/upload/v1712345678/khandani-legacy/profile-photos/abc123.jpg');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/image/destroy')
                // Folders kept, version and extension dropped.
                && str_contains($request->body(), 'khandani-legacy/profile-photos/abc123')
                && ! str_contains($request->body(), '.jpg');
        });
    }

    public function test_a_failed_remote_delete_never_breaks_the_save(): void
    {
        $this->withCloudinary();
        Http::fake(['api.cloudinary.com/*' => fn () => throw new \RuntimeException('network down')]);

        ImageStore::delete('https://res.cloudinary.com/test-cloud/image/upload/v1/khandani-legacy/x.jpg');

        // Reaching here without an exception is the assertion.
        $this->assertTrue(true);
    }
}
