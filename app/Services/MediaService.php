<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function store(UploadedFile $file, string $disk = 'public'): Media
    {
        $path     = $file->store('media', $disk);
        $url      = Storage::disk($disk)->url($path);

        return Media::create([
            'name'      => $file->getClientOriginalName(),
            'url'       => $url,
            'disk'      => $disk,
            'type'      => $this->resolveType($file),
            'size'      => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    public function delete(Media $media): void
    {
        // Extract relative path from URL for deletion
        $path = str_replace(Storage::disk($media->disk)->url(''), '', $media->url);
        Storage::disk($media->disk)->delete($path);
        $media->delete();
    }

    private function resolveType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';

        return 'image';
    }
}
