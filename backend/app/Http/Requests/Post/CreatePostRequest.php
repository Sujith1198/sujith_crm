<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $allowedImageTypes = 'jpg,jpeg,png,gif,webp';
        $allowedVideoTypes = 'mp4,mov,avi,mkv';
        $maxSizeMb = (int) config('app.max_upload_size_mb', 50);

        return [
            'title'               => 'required|string|max:255',
            'caption'             => 'nullable|string|max:2200',
            'description'         => 'nullable|string',
            'hashtags'            => 'nullable|string|max:2200',
            'post_type'           => 'required|in:text,image,video,carousel,reel',
            'publish_at'          => 'nullable|date|after:now',
            'timezone'            => 'nullable|string|max:50',
            'platforms'           => 'required|array|min:1',
            'platforms.facebook'  => 'boolean',
            'platforms.instagram' => 'boolean',
            'media'               => 'nullable|array',
            'media.*'             => "file|mimes:{$allowedImageTypes},{$allowedVideoTypes}|max:" . ($maxSizeMb * 1024),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $platforms = $this->input('platforms', []);
            if (empty(array_filter($platforms))) {
                $v->errors()->add('platforms', 'At least one platform must be selected.');
            }

            // Carousel requires at least 2 images
            if ($this->post_type === 'carousel' && count($this->file('media', [])) < 2) {
                $v->errors()->add('media', 'Carousel posts require at least 2 images.');
            }
        });
    }
}
