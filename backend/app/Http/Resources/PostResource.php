<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'caption'              => $this->caption,
            'description'          => $this->description,
            'hashtags'             => $this->hashtags,
            'hashtags_array'       => $this->hashtags_array,
            'thumbnail_url'        => $this->thumbnail_url,
            'status'               => $this->status,
            'post_type'            => $this->post_type,
            'publish_at'           => $this->publish_at?->toIso8601String(),
            'timezone'             => $this->timezone,
            'platforms'            => $this->platforms,
            'post_to_facebook'     => $this->post_to_facebook,
            'post_to_instagram'    => $this->post_to_instagram,
            'facebook_post_id'     => $this->facebook_post_id,
            'instagram_media_id'   => $this->instagram_media_id,
            'error_message'        => $this->error_message,
            'retry_count'          => $this->retry_count,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
            'user'                 => new UserResource($this->whenLoaded('user')),
            'media'                => PostMediaResource::collection($this->whenLoaded('media')),
            'scheduled_posts'      => $this->whenLoaded('scheduledPosts'),
        ];
    }
}
