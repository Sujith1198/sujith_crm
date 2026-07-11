<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $maxSizeMb = (int) config('app.max_upload_size_mb', 50);
        return [
            'title'               => 'sometimes|string|max:255',
            'caption'             => 'nullable|string|max:2200',
            'description'         => 'nullable|string',
            'hashtags'            => 'nullable|string|max:2200',
            'post_type'           => 'sometimes|in:text,image,video,carousel,reel',
            'publish_at'          => 'nullable|date|after:now',
            'timezone'            => 'nullable|string|max:50',
            'platforms'           => 'sometimes|array',
            'platforms.facebook'  => 'boolean',
            'platforms.instagram' => 'boolean',
            'media'               => 'nullable|array',
            'media.*'             => 'file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv|max:' . ($maxSizeMb * 1024),
        ];
    }
}
