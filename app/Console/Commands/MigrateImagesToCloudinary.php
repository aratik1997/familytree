<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\RecordMedia;
use App\Support\ImageStore;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Moves pictures already stored on the server's own disk up to Cloudinary and
 * repoints the records at them.
 *
 * Only touches rows still holding a local path, so it is safe to run twice —
 * anything already on Cloudinary is skipped.
 */
class MigrateImagesToCloudinary extends Command
{
    protected $signature = 'app:migrate-images {--pretend : List what would move without uploading anything}';

    protected $description = 'Upload locally stored photos to Cloudinary and repoint the records at them';

    public function handle(): int
    {
        if (! ImageStore::usingCloudinary()) {
            $this->error('Cloudinary is not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET first.');

            return self::FAILURE;
        }

        $pretend = $this->option('pretend');
        $moved = 0;
        $missing = 0;
        $failed = 0;

        $jobs = [
            ['label' => 'profile photo', 'folder' => 'profile-photos',
                'rows' => Person::withTrashed()->whereNotNull('profile_photo_path')->get(), 'column' => 'profile_photo_path'],
            ['label' => 'record attachment', 'folder' => 'record-media',
                'rows' => RecordMedia::whereNotNull('path')->get(), 'column' => 'path'],
        ];

        foreach ($jobs as $job) {
            foreach ($job['rows'] as $row) {
                $stored = $row->{$job['column']};

                if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
                    continue;
                }

                if (! Storage::disk('public')->exists($stored)) {
                    $this->warn("  missing on disk: {$stored}");
                    $missing++;

                    continue;
                }

                if ($pretend) {
                    $this->line("  would move {$job['label']}: {$stored}");
                    $moved++;

                    continue;
                }

                try {
                    // Wrapped as an UploadedFile so the same upload path runs
                    // here as when someone uses the form.
                    $absolute = Storage::disk('public')->path($stored);
                    $file = new UploadedFile($absolute, basename($stored), null, null, true);

                    $url = ImageStore::put($file, $job['folder']);
                    $row->forceFill([$job['column'] => $url])->saveQuietly();

                    $this->line("  moved {$job['label']}: ".basename($stored));
                    $moved++;
                } catch (Throwable $e) {
                    $this->error("  failed {$stored}: ".$e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info($pretend
            ? "{$moved} picture(s) would move."
            : "{$moved} picture(s) moved, {$failed} failed, {$missing} missing on disk.");

        if (! $pretend && $moved > 0) {
            $this->line('The local copies were left in place; delete storage/app/public once you are satisfied.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
