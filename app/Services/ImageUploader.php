<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploader
{
    /**
     * Store an uploaded image, re-encoding it as WebP via GD.
     * Returns the generated filename (no path prefix).
     */
    public function store(UploadedFile $file, string $folder, string $nameHint): string
    {
        $contents = file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new \InvalidArgumentException('File bukan gambar yang valid atau formatnya tidak didukung.');
        }

        // Resize if width > 1200px (no upscale)
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > 1200) {
            $newWidth = 1200;
            $newHeight = (int) round($height * ($newWidth / $width));
            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        } else {
            // Ensure transparency is preserved even without resize
            imagealphablending($source, false);
            imagesavealpha($source, true);
        }

        // Determine output format and encode
        $supportsWebp = function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);

        if ($supportsWebp) {
            $extension = 'webp';
        } else {
            $extension = 'jpg';
        }

        $filename = Str::slug($nameHint).'-'.Str::random(12).'.'.$extension;

        // Encode to buffer
        ob_start();
        if ($supportsWebp) {
            imagewebp($source, null, 82);
        } else {
            imagejpeg($source, null, 82);
        }
        $encoded = ob_get_clean();
        imagedestroy($source);

        Storage::disk('home_images')->makeDirectory($folder);
        Storage::disk('home_images')->put($folder.'/'.$filename, $encoded);

        return $filename;
    }

    /**
     * Move an image file to the archive disk (not deleted from web).
     * Used when an image is replaced or a record is permanently deleted.
     */
    public function archive(?string $image, string $folder): void
    {
        if (empty($image) || $this->isExternalUrl($image)) {
            return;
        }

        $sourcePath = $folder.'/'.$image;

        if (! Storage::disk('home_images')->exists($sourcePath)) {
            return;
        }

        $contents = Storage::disk('home_images')->get($sourcePath);
        $archivePath = $folder.'/'.now()->format('Ymd_His').'_'.$image;
        Storage::disk('home_archive')->makeDirectory($folder);
        Storage::disk('home_archive')->put($archivePath, $contents);

        Storage::disk('home_images')->delete($sourcePath);
    }

    /**
     * Restore an image from the archive disk back to the public images disk.
     * Returns true if the file was found and restored.
     */
    public function restore(string $image, string $folder): bool
    {
        // Search archive for files ending with the image name
        $files = Storage::disk('home_archive')->files($folder);

        foreach (array_reverse($files) as $archivedFile) {
            if (Str::endsWith($archivedFile, '_'.$image) || basename($archivedFile) === $image) {
                $contents = Storage::disk('home_archive')->get($archivedFile);
                Storage::disk('home_images')->put($folder.'/'.$image, $contents);
                Storage::disk('home_archive')->delete($archivedFile);

                return true;
            }
        }

        return false;
    }

    /**
     * Purge archived files older than the given number of days.
     * Returns the count of files deleted.
     */
    public function purgeArchive(int $days): int
    {
        $cutoff = now()->subDays($days)->getTimestamp();
        $count = 0;

        $files = Storage::disk('home_archive')->allFiles();

        foreach ($files as $file) {
            $lastModified = Storage::disk('home_archive')->lastModified($file);
            if ($lastModified <= $cutoff) {
                Storage::disk('home_archive')->delete($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if the given image value is an external URL.
     */
    public function isExternalUrl(?string $image): bool
    {
        return ! empty($image) && Str::startsWith($image, ['http://', 'https://']);
    }
}
