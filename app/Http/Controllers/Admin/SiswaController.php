<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

// Models
use App\Models\Siswa;
use App\Models\Kelas;

class SiswaController extends Controller
{
    /**
     * Halaman register siswa
     */
    public function index()
    {
        $siswa = Siswa::with(['kelas', 'kejuruan'])
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        return Inertia::render('Admin/Siswa/Index', [
            'siswa' => $siswa,
        ]);
    }

    /**
     * Halaman register siswa
     */
    public function create()
    {
        return Inertia::render('Admin/Siswa/Create', [
            'kelas'    => Kelas::select('id', 'kelas')->get(),
        ]);
    }

    /**
     * Simpan data siswa
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],

            'nis'  => ['nullable', 'unique:siswa,nis'],
            'nisn' => ['nullable', 'digits:10', 'unique:siswa,nisn'],

            'kelas_id'    => ['required'],
        ]);

        Siswa::create([
            'nama_lengkap' => $request->nama_lengkap,
            'nis'          => $request->nis,
            'nisn'         => $request->nisn,
            'kelas_id'     => $request->kelas_id,

            // ID internal siswa (7 karakter)
            'id_siswa' => strtoupper(substr(uniqid(), 0, 7)),
            'status'   => 'Activated',
        ]);

        return redirect()
            ->route('admin.siswa.create')
            ->with('success', 'Siswa berhasil didaftarkan');
    }

    public function edit(Siswa $siswa)
    {
        return Inertia::render('Admin/Siswa/Edit', [
            'siswa'    => $siswa,
            'kelas'    => Kelas::select('id', 'kelas')->get(),
        ]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nis'  => ['nullable', 'unique:siswa,nis,' . $siswa->id],
            'nisn' => ['nullable', 'digits:10', 'unique:siswa,nisn,' . $siswa->id],
            'kelas_id'    => ['required'],
            'status'      => ['required', 'in:Activated,Deactivated'],
            'osis' => ['required', 'in:yes,no'],
        ]);

        $siswa->update($validated);

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil dihapus');
    }

    /**
 * Hapus semua siswa berdasarkan kelas_id (bulk delete)
 */
    public function destroyByKelas(Request $request)
    {
        $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);

        Siswa::where('kelas_id', $request->kelas_id)->delete();

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Semua siswa di kelas tersebut berhasil dihapus');
    }
}
