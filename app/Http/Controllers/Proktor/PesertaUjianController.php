<?php

namespace App\Http\Controllers\Proktor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Exports\PesertaExport;
use App\Imports\PesertaImport;
use Maatwebsite\Excel\Facades\Excel;

class PesertaUjianController extends Controller
{
    public function index()
    {
        // 🔹 Ambil semua peserta (untuk frontend pagination)
        $pesertaAll = Siswa::with(['kelas', 'user'])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($s) {
                return [
                    'id'        => $s->id_siswa,
                    'nama'      => $s->nama_lengkap,
                    'kelas'     => $s->kelas->kelas ?? '-',
                    'kelas_id'  => $s->kelas_id,
                    'status'    => $s->status,
                    'email'     => $s->user->email ?? '',
                ];
            });

        $kelasList = DB::table('kelas')
            ->orderBy('kelas')
            ->get(['id', 'kelas']);

        return inertia('Proktor/Peserta/Index', [
            'pesertaAll' => $pesertaAll,
            'kelasList'  => $kelasList,
            'title'      => 'Users Directory',
        ]);
    }

    public function showForm()
    {
        return inertia('Proktor/Peserta/Register', [
            'kelasList' => DB::table('kelas')->orderBy('kelas')->get(['id', 'kelas']),
            'title' => 'Register User',
            'flash' => session()->get('success'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'password'     => 'nullable|string|min:6',
            'kelas_id'     => 'required|exists:kelas,id',
        ]);

        // Generate id_siswa unik
        do {
            $id_siswa = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Siswa::where('id_siswa', $id_siswa)->exists());

        $user = User::create([
            'name'     => $request->nama_lengkap,
            'email'    => $request->email,
            'password' => bcrypt($request->password ?? 'password'),
        ]);

        Siswa::create([
            'id_siswa'     => $id_siswa,
            'nama_lengkap' => $request->nama_lengkap,
            'kelas_id'     => $request->kelas_id,
            'status'       => 'Activated',
            'user_id'      => $user->id,
        ]);

        return response()->json([
            'success' => 'Peserta berhasil didaftarkan. ID Siswa: ' . $id_siswa,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'kelas_id'     => 'required|exists:kelas,id',
            'status'       => 'required|in:Activated,Deactivated',
            'email'        => 'required|email|max:255',
            'password'     => 'nullable|string|min:6',
        ]);

        $peserta = Siswa::where('id_siswa', $id)->firstOrFail();

        $peserta->update([
            'nama_lengkap' => $request->nama_lengkap,
            'kelas_id'     => $request->kelas_id,
            'status'       => $request->status,
        ]);

        if ($peserta->user) {
            $peserta->user->update([
                'email'    => $request->email,
                'password' => $request->password
                    ? bcrypt($request->password)
                    : $peserta->user->password,
            ]);
        }

        return response()->json([
            'success' => 'Peserta berhasil diupdate.',
        ]);
    }

    public function destroy($id)
    {
        Siswa::where('id_siswa', $id)->delete();

        return response()->json([
            'success' => 'Peserta berhasil dihapus.',
        ]);
    }

    public function destroyAll(Request $request)
    {
        $query = Siswa::query();

        // hapus berdasarkan kelas jika ada filter
        if ($request->kelas_id) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $query->delete();

        return response()->json([
            'success' => $request->kelas_id
                ? 'Peserta sesuai kelas berhasil dihapus.'
                : 'Semua peserta berhasil dihapus.',
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new PesertaExport, 'template_peserta.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'excel' => 'required|mimes:xlsx,xls',
        ]);

        Excel::import(new PesertaImport(), $request->file('excel'));

        return redirect()
            ->route('proktor.peserta.index')
            ->with('success', 'Data peserta berhasil diimport!');
    }
}
