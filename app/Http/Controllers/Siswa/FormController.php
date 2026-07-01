<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Siswa;
use App\Models\Kelas;

class FormController extends Controller
{
    /**
     * Halaman form data siswa.
     * Guard: jika user sudah punya data siswa, redirect ke dashboard.
     */
    public function create(Request $request)
    {
        if ($request->user()->siswa) {
            return redirect()->route('siswa.dashboard')
                ->with('info', 'Data kamu sudah tersimpan.');
        }

        return Inertia::render('Siswa/Form/Create', [
            'kelas' => Kelas::select('id', 'kelas')->orderBy('kelas')->get(),
        ]);
    }

    /**
     * Simpan data siswa.
     */
    public function store(Request $request)
    {
        if ($request->user()->siswa) {
            return redirect()->route('siswa.dashboard')
                ->with('info', 'Data kamu sudah tersimpan sebelumnya.');
        }

        $request->validate([
            // Identitas utama
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'nis'            => ['nullable', 'string', 'min:7', 'max:20', 'unique:siswa,nis'],
            'nisn'           => ['required', 'digits:10', 'unique:siswa,nisn'],
            'kelas_id'       => ['required', 'exists:kelas,id'],

            // Data pribadi
            'tempat_lahir'   => ['nullable', 'string', 'max:100'],
            'tanggal_lahir'  => ['nullable', 'date', 'before:today', 'after:1990-01-01'],
            'jenis_kelamin'  => ['nullable', 'in:L,P'],
            'agama'          => ['nullable', 'in:Islam,Kristen,Katolik,Hindu,Buddha,Konghucu'],
            'no_hp'          => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,15}$/'],
            'no_hp_ortu'     => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,15}$/'],
            'alamat'         => ['nullable', 'string', 'max:500'],
            'kelurahan'      => ['nullable', 'string', 'max:100'],
            'kecamatan'      => ['nullable', 'string', 'max:100'],
            'kota'           => ['nullable', 'string', 'max:100'],
            'kode_pos'       => ['nullable', 'string', 'max:10', 'regex:/^\d{5}$/'],
        ], [
            'nama_lengkap.required'  => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'       => 'Nama lengkap terlalu panjang (maksimal 255 karakter).',
            'nis.min'                => 'NIS minimal 7 karakter.',
            'nis.unique'             => 'NIS ini sudah terdaftar, hubungi admin.',
            'nisn.required'          => 'NISN wajib diisi.',
            'nisn.digits'            => 'NISN harus tepat 10 digit angka.',
            'nisn.unique'            => 'NISN ini sudah terdaftar, hubungi admin.',
            'kelas_id.required'      => 'Silakan pilih kelas terlebih dahulu.',
            'kelas_id.exists'        => 'Kelas yang dipilih tidak valid.',
            'tanggal_lahir.before'   => 'Tanggal lahir tidak boleh hari ini atau masa depan.',
            'tanggal_lahir.after'    => 'Tanggal lahir tidak valid.',
            'jenis_kelamin.in'       => 'Jenis kelamin tidak valid.',
            'agama.in'               => 'Agama yang dipilih tidak valid.',
            'no_hp.regex'            => 'Format nomor HP tidak valid (8–15 digit).',
            'no_hp_ortu.regex'       => 'Format nomor HP orang tua tidak valid (8–15 digit).',
            'alamat.max'             => 'Alamat terlalu panjang (maksimal 500 karakter).',
            'kode_pos.regex'         => 'Kode pos harus 5 digit angka.',
        ]);

        try {
            Siswa::create([
                // Identitas
                'user_id'       => auth()->id(),
                // nama_lengkap: selalu simpan dalam format Title Case (kapitalisasi tiap kata)
                // Ini adalah fallback keamanan di sisi server — tidak bergantung pada input user
                'nama_lengkap'  => $this->toTitleCase(trim($request->nama_lengkap)),
                'nis'           => $request->nis ? trim($request->nis) : null,
                'nisn'          => trim($request->nisn),
                'kelas_id'      => $request->kelas_id,
                'id_siswa'      => strtoupper(substr(uniqid(), 0, 7)),
                'status'        => 'Activated',

                // Data pribadi
                'tempat_lahir'  => $request->tempat_lahir ? $this->toTitleCase(trim($request->tempat_lahir)) : null,
                'tanggal_lahir' => $request->tanggal_lahir ?: null,
                'jenis_kelamin' => $request->jenis_kelamin ?: null,
                'agama'         => $request->agama ?: null,
                'no_hp'         => $request->no_hp ? trim($request->no_hp) : null,
                'no_hp_ortu'    => $request->no_hp_ortu ? trim($request->no_hp_ortu) : null,
                'alamat'        => $request->alamat ? trim($request->alamat) : null,
                'kelurahan'     => $request->kelurahan ? $this->toTitleCase(trim($request->kelurahan)) : null,
                'kecamatan'     => $request->kecamatan ? $this->toTitleCase(trim($request->kecamatan)) : null,
                'kota'          => $request->kota ? $this->toTitleCase(trim($request->kota)) : null,
                'kode_pos'      => $request->kode_pos ? trim($request->kode_pos) : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('FormController@store failed: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }

        return redirect()
            ->route('siswa.dashboard')
            ->with('success', 'Data berhasil disimpan! Selamat datang 🎉');
    }

    /**
     * Konversi string ke Title Case (mendukung multi-kata & karakter Indonesia).
     * Contoh: "budi santoso" → "Budi Santoso"
     *         "BUDI SANTOSO" → "Budi Santoso"
     */
    private function toTitleCase(string $value): string
    {
        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }
}