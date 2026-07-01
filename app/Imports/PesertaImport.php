<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use App\Models\Kejuruan;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PesertaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip baris kosong
        if (empty($row['nama_lengkap']) || empty($row['email']) || empty($row['kelas']) || empty($row['kejuruan'])) {
            return null;
        }

        $kelas = Kelas::where('kelas', $row['kelas'])->first();
        $kejuruan = Kejuruan::where('kejuruan', $row['kejuruan'])->first();

        if (!$kelas || !$kejuruan) return null;

        // generate id_siswa unik
        do {
            $id_siswa = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Siswa::where('id_siswa', $id_siswa)->exists());

        // buat user jika belum ada
        $user = User::firstOrCreate(
            ['email' => $row['email']],
            [
                'name'     => $row['nama_lengkap'],
                'password' => Hash::make('password'),
            ]
        );

        // buat siswa
        return Siswa::create([
            'id_siswa'     => $id_siswa,
            'nama_lengkap' => $row['nama_lengkap'],
            'kelas_id'     => $kelas->id,
            'kejuruan_id'  => $kejuruan->id,
            'status'       => 'Activated',
            'user_id'      => $user->id,
        ])->load('user');
    }
}
