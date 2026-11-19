<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Uploads (Dev Handoff UploadField / Spec 11): images JPG/PNG/WebP ≤ 10 MB (resized to
 * ≤ 1920px and re-encoded, which also strips EXIF/GPS), video ≤ 50 MB, slips ≤ 10 MB.
 * Everything is stored on the private `local` disk and streamed through MediaController,
 * which checks the viewer's permission first (replaces Supabase Storage policies).
 */
class MediaService
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const VIDEO_MIMES = ['video/mp4', 'video/webm'];

    public const IMAGE_MAX_KB = 10240;

    public const VIDEO_MAX_KB = 51200;

    public const MAX_EDGE = 1920;

    /** @return array{path:string, mime:string, size:int} */
    public function storeImage(UploadedFile $file, string $folder): array
    {
        $mime = $file->getMimeType();
        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw ValidationException::withMessages(['photo' => 'ຮອງຮັບສະເພາະ JPG, PNG ຫຼື WebP']);
        }
        if ($file->getSize() > self::IMAGE_MAX_KB * 1024) {
            throw ValidationException::withMessages(['photo' => 'ຮູບຕ້ອງບໍ່ເກີນ 10 MB']);
        }

        $binary = $this->resize($file->getRealPath(), $mime);
        $path = trim($folder, '/').'/'.Str::uuid().'.'.($binary ? 'webp' : $file->extension());
        Storage::disk('local')->put($path, $binary ?? file_get_contents($file->getRealPath()));

        return ['path' => $path, 'mime' => $binary ? 'image/webp' : $mime, 'size' => Storage::disk('local')->size($path)];
    }

    /** Image or video for event / news galleries. @return array{path:string, mime:string, size:int} */
    public function storeMedia(UploadedFile $file, string $folder): array
    {
        $mime = $file->getMimeType();
        if (in_array($mime, self::VIDEO_MIMES, true)) {
            if ($file->getSize() > self::VIDEO_MAX_KB * 1024) {
                throw ValidationException::withMessages(['files' => 'ວິດີໂອຕ້ອງບໍ່ເກີນ 50 MB']);
            }
            $path = $file->store(trim($folder, '/'), 'local');

            return ['path' => $path, 'mime' => $mime, 'size' => $file->getSize()];
        }

        return $this->storeImage($file, $folder);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /** Down-scale to MAX_EDGE and re-encode as WebP. Returns null when GD cannot handle it. */
    private function resize(string $source, string $mime): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }
        $img = @imagecreatefromstring((string) file_get_contents($source));
        if (! $img) {
            throw ValidationException::withMessages(['photo' => 'ໄຟລ໌ຮູບເສຍ ຫຼື ອ່ານບໍ່ໄດ້']);
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($source)['Orientation'] ?? 1;
            $img = match ((int) $orientation) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => $img,
            };
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, self::MAX_EDGE / max($w, $h));
        if ($scale < 1) {
            $nw = (int) round($w * $scale);
            $nh = (int) round($h * $scale);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }
        ob_start();
        imagewebp($img, null, 82);
        imagedestroy($img);

        return ob_get_clean() ?: null;
    }
}
