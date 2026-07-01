<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PesertaUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $peserta;

    public function __construct($peserta)
    {
        // Kalau peserta adalah model, ubah ke array
        $this->peserta = is_array($peserta) ? $peserta : $peserta->toArray();
    }

    public function broadcastOn()
    {
        return new Channel('ruang-ujian');
    }

    public function broadcastAs()
    {
        return 'PesertaUpdated';
    }

    public function broadcastWith()
    {
        $p = is_array($this->peserta) ? $this->peserta : $this->peserta->toArray();

        return [
            'peserta' => [
                'id' => $p['id'] ?? null,
                'user' => [
                    'siswa' => [
                        'nama_lengkap' => $p['user']['siswa']['nama_lengkap'] ?? null,
                        'kelas' => [
                            'kelas' => $p['user']['siswa']['kelas']['kelas'] ?? null
                        ]
                    ]
                ],
                'status' => $p['status'] ?? null,
                'token' => $p['token'] ?? null,
            ]
        ];
    }

}
