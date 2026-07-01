```sh
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
        $this->peserta = $peserta;
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
        $p = $this->peserta;

        // Cek apakah peserta adalah object atau array
        $get = fn($obj, $key, $default = null) => is_array($obj) ? ($obj[$key] ?? $default) : ($obj->$key ?? $default);

        return [
            'peserta' => [
                'id' => $get($p, 'id'),
                'user' => [
                    'siswa' => [
                        'nama_lengkap' => $get($get($get($p, 'user', []), 'siswa', []), 'nama_lengkap'),
                        'kelas' => [
                            'kelas' => $get($get($get($get($p, 'user', []), 'siswa', []), 'kelas', []), 'kelas')
                        ]
                    ]
                ],
                'status' => $get($p, 'status'),
                'token' => $get($p, 'token'),
            ]
        ];
    }
}
```
