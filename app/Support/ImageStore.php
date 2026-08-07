<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Where the family's pictures live.
 *
 * With Cloudinary configured, an upload goes straight there and what we keep
 * in the database is the delivered URL — so showing a picture is just printing
 * the string, with no filesystem, symlink or web-root arrangement involved.
 * That is the whole point: shared hosting kept getting those wrong.
 *
 * Without Cloudinary configured it behaves exactly as before, writing to the
 * public disk. Both forms are readable side by side, so the pictures uploaded
 * before the switch keep working untouched.
 *
 * Talks to Cloudinary's REST endpoint directly rather than through their SDK:
 * one less dependency to install on a host where Composer may not run.
 */
class ImageStore
{
    /** True once Cloudinary has everything it needs. */
    public static function usingCloudinary(): bool
    {
        $config = config('services.cloudinary');

        return filled($config['cloud_name'] ?? null)
            && filled($config['key'] ?? null)
            && filled($config['secret'] ?? null);
    }

    /**
     * Stores an uploaded picture and returns the reference to keep in the
     * database — a full URL from Cloudinary, or a path on the public disk.
     */
    public static function put(UploadedFile $file, string $folder): string
    {
        if (! static::usingCloudinary()) {
            return $file->storeAs($folder, Str::uuid().'.'.$file->extension(), 'public');
        }

        return static::uploadToCloudinary($file, $folder);
    }

    /**
     * The address to put in an <img src>. Anything already absolute is passed
     * straight back, which is what makes old local paths and new Cloudinary
     * URLs work side by side.
     */
    public static function url(?string $stored): ?string
    {
        if (blank($stored)) {
            return null;
        }

        if (Str::startsWith($stored, ['http://', 'https://'])) {
            return $stored;
        }

        return Storage::disk('public')->url($stored);
    }

    /**
     * Removes a picture we are replacing.
     *
     * Cloudinary deletions are deliberately best-effort: failing to tidy up a
     * remote copy must never break saving a profile, and an orphaned image
     * costs nothing but storage.
     */
    public static function delete(?string $stored): void
    {
        if (blank($stored)) {
            return;
        }

        if (! Str::startsWith($stored, ['http://', 'https://'])) {
            Storage::disk('public')->delete($stored);

            return;
        }

        $publicId = static::publicIdFromUrl($stored);

        if (! $publicId || ! static::usingCloudinary()) {
            return;
        }

        try {
            $config = config('services.cloudinary');
            $timestamp = time();

            Http::asMultipart()
                ->timeout(15)
                ->post("https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/destroy", [
                    ['name' => 'public_id', 'contents' => $publicId],
                    ['name' => 'api_key', 'contents' => $config['key']],
                    ['name' => 'timestamp', 'contents' => (string) $timestamp],
                    ['name' => 'signature', 'contents' => static::sign(
                        ['public_id' => $publicId, 'timestamp' => $timestamp],
                        $config['secret']
                    )],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Could not remove an image from Cloudinary.', [
                'public_id' => $publicId,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private static function uploadToCloudinary(UploadedFile $file, string $folder): string
    {
        $config = config('services.cloudinary');
        $timestamp = time();

        // Everything except the file, api_key and resource_type is signed.
        $signed = [
            'folder' => trim($config['folder'] ?? 'khandani-legacy', '/').'/'.$folder,
            'timestamp' => $timestamp,
        ];

        $response = Http::asMultipart()
            ->timeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: 'upload')
            ->post("https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/upload", [
                ['name' => 'api_key', 'contents' => $config['key']],
                ['name' => 'timestamp', 'contents' => (string) $timestamp],
                ['name' => 'folder', 'contents' => $signed['folder']],
                ['name' => 'signature', 'contents' => static::sign($signed, $config['secret'])],
            ]);

        if ($response->failed()) {
            // Cloudinary puts the useful part in error.message.
            $reason = $response->json('error.message') ?? $response->body();

            Log::error('Cloudinary rejected an upload.', ['status' => $response->status(), 'reason' => $reason]);

            throw new RuntimeException('The picture could not be uploaded: '.$reason);
        }

        $url = $response->json('secure_url');

        if (blank($url)) {
            throw new RuntimeException('Cloudinary accepted the upload but returned no address for it.');
        }

        return $url;
    }

    /** Cloudinary's scheme: sorted `key=value` pairs, then the API secret. */
    private static function sign(array $params, string $secret): string
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key.'='.$value;
        }

        return sha1(implode('&', $pairs).$secret);
    }

    /**
     * Recovers the public id from a delivery URL, so a replaced picture can be
     * deleted. Handles the version segment (/v1712345678/) and strips the
     * extension, keeping any folders.
     */
    private static function publicIdFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! $path || ! preg_match('~/upload/(?:[^/]+/)*?(?:v\d+/)?(.+)$~', $path, $matches)) {
            return null;
        }

        return preg_replace('~\.[^./]+$~', '', $matches[1]);
    }
}
