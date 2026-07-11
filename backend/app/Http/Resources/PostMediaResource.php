<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'url'              => $this->url,
            'thumbnail_url'    => $this->thumbnail_url,
            'file_name'        => $this->file_name,
            'mime_type'        => $this->mime_type,
            'file_size'        => $this->file_size,
            'file_size_human'  => $this->file_size_human,
            'width'            => $this->width,
            'height'           => $this->height,
            'duration'         => $this->duration,
            'sort_order'       => $this->sort_order,
        ];
    }
}
