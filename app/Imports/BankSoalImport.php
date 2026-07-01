<?php

namespace App\Imports;

use App\Models\BankSoal;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankSoalImport implements ToModel, WithHeadingRow
{
    protected $soal_id;

    public function __construct($soal_id)
    {
        $this->soal_id = $soal_id;
    }

    // public function model(array $row)
    // {
    //     // Ubah tipe soal
    //     $tipe = $row['tipe_soal'] === 'Pilihan Ganda' ? 'PG' : 'Essay';

    //     // Ubah jawaban benar ke field opsi
    //     $jawaban_map = [
    //         'A' => 'opsi_a',
    //         'B' => 'opsi_b',
    //         'C' => 'opsi_c',
    //         'D' => 'opsi_d',
    //         'E' => 'opsi_e',
    //     ];
    //     $jawaban_benar = $jawaban_map[$row['jawaban_benar']] ?? null;

    //     return new BankSoal([
    //         'soal_id' => $this->soal_id,
    //         'soal' => $row['soal'],
    //         'tipe_soal' => $tipe,
    //         'jenis_lampiran' => $row['jenis_lampiran'] ?? 'Tanpa Lampiran',
    //         'link_lampiran' => $row['link_lampiran'] ?? null,
    //         'jawaban_benar' => $jawaban_benar,
    //         'opsi_a' => $row['opsi_a'] ?? null,
    //         'opsi_b' => $row['opsi_b'] ?? null,
    //         'opsi_c' => $row['opsi_c'] ?? null,
    //         'opsi_d' => $row['opsi_d'] ?? null,
    //         'opsi_e' => $row['opsi_e'] ?? null,
    //         'nilai' => $row['nilai'] ?? 0,
    //     ]);
    // }

    public function model(array $row)
{
    // ✅ skip kalau row kosong semua
    if (empty(array_filter($row))) {
        return null;
    }

    // ✅ pastikan key ada (hindari undefined index)
    if (!isset($row['soal']) || trim($row['soal']) === '') {
        return null; // skip baris tanpa soal
    }

    // ✅ tipe soal aman
    $tipe = (isset($row['tipe_soal']) && $row['tipe_soal'] === 'Pilihan Ganda')
        ? 'PG'
        : 'Essay';

    // ✅ mapping jawaban aman
    $jawaban_map = [
        'A' => 'opsi_a',
        'B' => 'opsi_b',
        'C' => 'opsi_c',
        'D' => 'opsi_d',
        'E' => 'opsi_e',
    ];

    $jawaban_key = strtoupper($row['jawaban_benar'] ?? '');
    $jawaban_benar = $jawaban_map[$jawaban_key] ?? null;

    return new BankSoal([
        'soal_id' => $this->soal_id,
        'soal' => $row['soal'],
        'tipe_soal' => $tipe,
        'jenis_lampiran' => $row['jenis_lampiran'] ?? 'Tanpa Lampiran',
        'link_lampiran' => $row['link_lampiran'] ?? null,
        'jawaban_benar' => $jawaban_benar,
        'opsi_a' => $row['opsi_a'] ?? null,
        'opsi_b' => $row['opsi_b'] ?? null,
        'opsi_c' => $row['opsi_c'] ?? null,
        'opsi_d' => $row['opsi_d'] ?? null,
        'opsi_e' => $row['opsi_e'] ?? null,
        'nilai' => $row['nilai'] ?? 0,
    ]);
}
}
