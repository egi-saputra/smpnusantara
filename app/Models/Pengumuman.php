<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'pengumuman',
        'user_id',
        'file_path',
        'video_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}