<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'program'    => $this->program,
            // Label singkat untuk badge di tabel
            'program_short' => $this->programShortLabel(),
            'message'    => $this->message,
            'status'     => $this->status,
            'status_label' => $this->statusLabel(),
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_human' => $this->created_at->locale('id')->diffForHumans(),
            'last_submission_at' => $this->last_submission_at?->toIso8601String(),

            // Data device hanya untuk admin yang terautentikasi
            $this->mergeWhen($request->user()?->is_admin, [
                'ip_address'  => $this->ip_address,
                'user_agent'  => $this->user_agent,
                'device_info' => $this->device_info,
            ]),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending',
            'contacted' => 'Sedang Diproses',
            'enrolled'  => 'Diterima',
            'rejected'  => 'Ditolak',
            default     => $this->status,
        };
    }

    private function programShortLabel(): string
    {
        return match (true) {
            str_starts_with($this->program, 'MPLB') => 'MPLB',
            str_starts_with($this->program, 'BR')   => 'BR',
            default => $this->program,
        };
    }
}