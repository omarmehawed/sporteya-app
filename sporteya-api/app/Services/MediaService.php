<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload an avatar image and return the public URL.
     */
    public function uploadAvatar(UploadedFile $file): string
    {
        return $this->upload($file, 'sporteya/avatars');
    }

    /**
     * Upload venue media and return the public URL.
     */
    public function uploadVenuePhoto(UploadedFile $file): string
    {
        return $this->upload($file, 'sporteya/venues');
    }

    /**
     * Generic upload helper.
     */
    public function upload(UploadedFile $file, string $folder = 'sporteya'): string
    {
        if (config('cloudinary.cloud_url') || env('CLOUDINARY_URL')) {
            try {
                $uploadResult = Cloudinary::upload($file->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => 'image',
                ]);
                return $uploadResult->getSecurePath();
            } catch (\Throwable $e) {
                // If Cloudinary fails or credentials invalid in dev/test, fallback to local
                report($e);
            }
        }

        $filename = Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs($folder, $filename, 'public');

        return Storage::disk('public')->url($path);
    }
}
