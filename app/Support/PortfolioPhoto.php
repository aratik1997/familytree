<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * The portrait on a portfolio page.
 *
 * Kept apart from the family tree's own pictures on purpose: this is the photo
 * someone chose for their public website, which is not always the one that
 * belongs on a family record. Uploading here never touches their tree profile.
 *
 * The page asks for img/<slug>.jpg beside itself, so publishing is a copy into
 * the page's own folder — the same arrangement as data.json, and for the same
 * reason: each portfolio is its own subdomain and cannot read the main site.
 * The original is kept under storage/ so a republish can always restore it.
 */
class PortfolioPhoto
{
    /** Anything larger is a phone camera's full frame; the page shows ~600px. */
    private const MAX_EDGE = 1200;

    private const QUALITY = 82;

    /** What a browser may send. Anything else is refused before GD sees it. */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * The largest picture this server will actually accept, in kilobytes.
     *
     * Read from PHP rather than written down, because a limit we state and the
     * host does not enforce is worse than useless: an upload past the host's
     * own ceiling never reaches the application at all, and the person is left
     * looking at a form that appears to have worked.
     *
     * post_max_size counts too — it caps the whole request, so a file just
     * under upload_max_filesize can still be thrown away with the rest of the
     * form around it.
     */
    public static function maxKilobytes(): int
    {
        $limits = array_filter([
            static::iniBytes('upload_max_filesize'),
            // Leave room for the rest of the form: the wording, the lists and
            // the layout travel in the same request as the picture.
            static::iniBytes('post_max_size') - 512 * 1024,
        ], fn ($n) => $n > 0);

        $bytes = $limits ? min($limits) : 2 * 1024 * 1024;

        // Never offer more than the pages need; a bigger file is only a slower
        // upload for a portrait that gets shrunk to 1200px regardless.
        return (int) max(256, min($bytes, 8 * 1024 * 1024) / 1024);
    }

    /** Reads a php.ini size, which may be written as 8M or 512K. */
    private static function iniBytes(string $key): int
    {
        $raw = trim((string) ini_get($key));

        if ($raw === '') {
            return 0;
        }

        $n = (int) $raw;

        return match (strtoupper(substr($raw, -1))) {
            'G' => $n * 1024 ** 3,
            'M' => $n * 1024 ** 2,
            'K' => $n * 1024,
            default => $n,
        };
    }

    public static function path(string $slug): string
    {
        return storage_path("app/portfolio-photos/{$slug}.jpg");
    }

    public static function exists(string $slug): bool
    {
        return File::exists(static::path($slug));
    }

    /** Changes whenever the photo does, so a browser cannot show a stale one. */
    public static function version(string $slug): ?int
    {
        return static::exists($slug) ? File::lastModified(static::path($slug)) : null;
    }

    /**
     * Stores an uploaded portrait, re-encoded as a JPEG the page can use.
     *
     * Always re-encoded rather than kept as sent: it caps a 6MB phone photo at
     * something a page can load, and it means whatever arrives is written out
     * by GD as a plain JPEG — a file that is only pretending to be an image
     * does not survive the round trip.
     */
    public static function put(string $slug, UploadedFile $file): void
    {
        $image = static::read($file->getRealPath());

        try {
            $image = static::orient($image, $file->getRealPath());
            $image = static::fit($image);

            File::ensureDirectoryExists(dirname(static::path($slug)));

            if (! imagejpeg($image, static::path($slug), static::QUALITY)) {
                throw new RuntimeException('the picture could not be saved');
            }
        } finally {
            imagedestroy($image);
        }
    }

    public static function delete(string $slug): void
    {
        File::delete(static::path($slug));
    }

    /**
     * Lays the photo beside the built page, where the page looks for it.
     *
     * Returns what happened, in the same words publish() uses for the wording,
     * so one screen can report on both.
     */
    public static function publish(string $slug): string
    {
        $dir = PortfolioContent::pageDir($slug);

        if (! File::isDirectory($dir)) {
            return 'no page folder on this server';
        }

        if (! static::exists($slug)) {
            return 'no photo uploaded';
        }

        File::ensureDirectoryExists($dir.'/img');

        return File::copy(static::path($slug), $dir."/img/{$slug}.jpg")
            ? 'published'
            : 'could not write';
    }

    /** Reads a file into GD, whatever of the three formats it is. */
    private static function read(string $path)
    {
        $info = @getimagesize($path);
        $image = match ($info['mime'] ?? null) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image) {
            throw new RuntimeException('that file is not a picture this can read');
        }

        return $image;
    }

    /**
     * Turns a photo the right way up.
     *
     * A phone writes the orientation into EXIF rather than rotating the pixels,
     * and GD reads the pixels — so a portrait taken on a phone arrives on its
     * side unless this is done.
     */
    private static function orient($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated;
    }

    /** Shrinks the long edge to MAX_EDGE, leaving anything smaller alone. */
    private static function fit($image)
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = self::MAX_EDGE / max($w, $h);

        if ($scale >= 1) {
            // Still flattened onto white below, so a transparent PNG does not
            // become a black rectangle once it is written as a JPEG.
            $scale = 1;
        }

        $to = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
        imagefilledrectangle($to, 0, 0, imagesx($to), imagesy($to), imagecolorallocate($to, 255, 255, 255));
        imagecopyresampled($to, $image, 0, 0, 0, 0, imagesx($to), imagesy($to), $w, $h);
        imagedestroy($image);

        return $to;
    }
}
