<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentRevision extends Model
{
    use HasFactory;

     protected $fillable = [
        'tugas_id',
        'judul',
        'deskripsi',
        'file_path',
        'catatan_revisi',
        'revision_number',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'tugas_id');
    }
}
