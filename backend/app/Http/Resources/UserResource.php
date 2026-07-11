<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'avatar'        => $this->avatar,
            'avatar_url'    => $this->avatar_url,
            'status'        => $this->status,
            'timezone'      => $this->timezone,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_login_ip' => $this->last_login_ip,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
            'roles'         => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions'   => $this->whenLoaded('roles', fn () =>
                $this->getAllPermissions()->pluck('name')
            ),
            'social_accounts' => SocialAccountResource::collection($this->whenLoaded('socialAccounts')),
        ];
    }
}
