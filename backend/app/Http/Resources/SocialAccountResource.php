<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'platform'            => $this->platform,
            'account_name'        => $this->account_name,
            'page_id'             => $this->page_id,
            'page_name'           => $this->page_name,
            'account_id'          => $this->account_id,
            'profile_picture_url' => $this->profile_picture_url,
            'followers_count'     => $this->followers_count,
            'auto_refresh_token'  => $this->auto_refresh_token,
            'is_active'           => $this->is_active,
            'token_expires_at'    => $this->token_expires_at?->toIso8601String(),
            'token_expired'       => $this->isTokenExpired(),
            'token_expiring_soon' => $this->isTokenExpiringSoon(),
            'last_synced_at'      => $this->last_synced_at?->toIso8601String(),
            'metadata'            => $this->metadata,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
