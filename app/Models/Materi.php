<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'kelas_id',
        'mapel_id',
        'guru_id',
        'file_path',
    ];

    // 🔗 relasi ke guru
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'user_id', 'user_id');
    }

    // 🔗 relasi ke mapel
    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    // 🔗 relasi ke kelas (opsional tapi bagus)
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
