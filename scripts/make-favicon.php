<?php

/**
 * Draws the crest from resources/views/components/application-logo.blade.php as
 * a favicon: a gold shield on the deep green of the site, with the three-person
 * tree knocked out of it in green.
 *
 * Filled rather than outlined, because a 2.2px stroke at 16 wide is a smudge.
 */

const GREEN = [0x0B, 0x3D, 0x2E];   // --ink-900
const GOLD = [0xC9, 0xA2, 0x27];    // --gold-500

/** Quadratic bezier, sampled into points. */
function curve(array $from, array $control, array $to, int $steps = 24): array
{
    $points = [];

    for ($i = 1; $i <= $steps; $i++) {
        $t = $i / $steps;
        $u = 1 - $t;

        $points[] = [
            $u * $u * $from[0] + 2 * $u * $t * $control[0] + $t * $t * $to[0],
            $u * $u * $from[1] + 2 * $u * $t * $control[1] + $t * $t * $to[1],
        ];
    }

    return $points;
}

/**
 * The shield outline on a 64x64 grid, matching the logo's path: flat shoulders,
 * a point at the top, and sides sweeping down to a point at the bottom.
 */
function shield(): array
{
    $points = [[32, 3], [56, 12], [56, 30]];
    $points = array_merge($points, curve([56, 30], [56, 50], [32, 61]));
    $points = array_merge($points, curve([32, 61], [8, 50], [8, 30]));
    $points[] = [8, 12];

    return $points;
}

function render(int $size)
{
    // Drawn at 8x and scaled down, which is the cheapest antialiasing there is
    // and the only kind that survives GD's polygon filling.
    $scale = 8;
    $big = $size * $scale;
    $k = $big / 64;

    $canvas = imagecreatetruecolor($big, $big);
    imagealphablending($canvas, true);

    $green = imagecolorallocate($canvas, ...GREEN);
    $gold = imagecolorallocate($canvas, ...GOLD);

    imagefilledrectangle($canvas, 0, 0, $big, $big, $green);

    $polygon = [];
    foreach (shield() as [$x, $y]) {
        $polygon[] = $x * $k;
        $polygon[] = $y * $k;
    }
    imagefilledpolygon($canvas, array_map('intval', $polygon), $gold);

    // The three people and the lines between them, cut back to green. Kept
    // chunky: anything finer disappears at 16 pixels.
    //
    // The crossbar sits at the children's own centre height and runs from one
    // centre to the other, so both of its ends finish inside a circle. Ending
    // it short of them, as the full logo does with its thin stroke, leaves the
    // flat cap standing proud of the dot as a square notch once the line is
    // thick enough to read small.
    // Small sizes get a leaner mark. Three dots of the size that reads well on
    // a 512px tile fill most of a 16px shield and come out as one dark blob, so
    // below about 24 the dots shrink and the strokes thin — same drawing, less
    // of it, which is what survives.
    $small = $size < 24;

    $parent = $small ? [32, 22, 4.4] : [32, 21, 6.0];
    $children = $small
        ? [[20, 38, 4.0], [44, 38, 4.0]]
        : [[21, 39, 5.2], [43, 39, 5.2]];

    imagesetthickness($canvas, max(1, (int) round(($small ? 2.0 : 2.6) * $k)));
    imageline($canvas, (int) ($parent[0] * $k), (int) ($parent[1] * $k), (int) ($parent[0] * $k), (int) ($children[0][1] * $k), $green);
    imageline($canvas, (int) ($children[0][0] * $k), (int) ($children[0][1] * $k), (int) ($children[1][0] * $k), (int) ($children[1][1] * $k), $green);

    foreach ([$parent, ...$children] as [$cx, $cy, $r]) {
        $d = (int) round($r * 2 * $k);
        imagefilledellipse($canvas, (int) round($cx * $k), (int) round($cy * $k), $d, $d, $green);
    }

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
    imagedestroy($canvas);

    return $out;
}

function pngBytes($image): string
{
    ob_start();
    imagepng($image, null, 9);

    return ob_get_clean();
}

/**
 * An .ico holding PNGs — every browser in use reads this, and it avoids having
 * to write BMP with its upside-down rows and mask plane.
 */
function ico(array $pngsBySize): string
{
    $count = count($pngsBySize);
    $header = pack('vvv', 0, 1, $count);
    $offset = 6 + 16 * $count;

    $entries = '';
    $bodies = '';

    foreach ($pngsBySize as $size => $png) {
        $entries .= pack(
            'CCCCvvVV',
            $size >= 256 ? 0 : $size,
            $size >= 256 ? 0 : $size,
            0, 0, 1, 32,
            strlen($png),
            $offset
        );

        $bodies .= $png;
        $offset += strlen($png);
    }

    return $header.$entries.$bodies;
}

$root = 'c:/xampp/htdocs/familytree/public';

$pngs = [];
foreach ([16, 32, 48] as $size) {
    $image = render($size);
    $pngs[$size] = pngBytes($image);
    imagedestroy($image);
}

file_put_contents($root.'/favicon.ico', ico($pngs));
echo 'favicon.ico: '.filesize($root.'/favicon.ico')." bytes\n";

foreach ([180 => 'apple-touch-icon.png', 512 => 'icon-512.png'] as $size => $name) {
    $image = render($size);
    imagepng($image, $root.'/'.$name, 9);
    imagedestroy($image);
    echo $name.': '.filesize($root.'/'.$name)." bytes\n";
}
