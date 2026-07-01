<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WalasController extends Controller
{
    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────

    /**
     * Resolve guru yang sedang login beserta kelas yang di-walasi.
     * Throws 404 jika guru tidak ditemukan.
     */
    private function resolveGuru(): Guru
    {
        return Guru::where('user_id', auth()->id())->firstOrFail();
    }

    private function resolveKelas(Guru $guru): ?Kelas
    {
        return Kelas::where('guru_id', $guru->id)->first();
    }

    /**
     * Pastikan siswa $siswa memang ada di kelas wali guru ini.
     * Mencegah IDOR (Insecure Direct Object Reference).
     */
    private function authorizeStudent(Siswa $siswa, Kelas $kelas): void
    {
        // kelas_id di tabel siswa bisa berupa int atau string — bandingkan cast-safe
        abort_if((int) $siswa->kelas_id !== (int) $kelas->id, 403, 'Siswa tidak berada di kelas Anda.');
    }

    // ─────────────────────────────────────────────
    //  Index
    // ─────────────────────────────────────────────

    public function index(Request $request)
    {
        $guru  = $this->resolveGuru();
        $kelas = $this->resolveKelas($guru);

        if (! $kelas) {
            return Inertia::render('Guru/Walas/Index', [
                'kelas'   => null,
                'guru'    => $guru->only(['id', 'nama_lengkap', 'jabatan']),
                'siswa'   => null,
                'stats'   => null,
                'filters' => [],
            ]);
        }

        // ── Query utama ──
        $query = Siswa::with('kelas')               // eager-load agar tidak N+1
                      ->where('kelas_id', $kelas->id); // int vs int — tidak perlu cast string

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nis',        'like', "%{$search}%")
                  ->orWhere('nisn',       'like', "%{$search}%")
                  ->orWhere('id_siswa',   'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['Activated', 'Deactivated'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            switch ($request->role) {
                case 'sekretaris':
                    $query->where('sekretaris', 'yes');
                    break;

                case 'bendahara':
                    $query->where('bendahara', 'yes');
                    break;

                case 'osis':
                    $query->where('osis', 'yes');
                    break;
            }
        }

        $siswa = $query
            ->select([
                'id', 'nama_lengkap', 'nis', 'nisn', 'id_siswa',
                'kelas_id', 'kejuruan_id', 'status',
                'sekretaris', 'bendahara', 'osis',
            ])
            ->orderBy('nama_lengkap')
            ->paginate(20)
            ->withQueryString();          // <-- ini yang mengikat filter ke link paginasi

        // ── Statistik (tidak terpengaruh filter) ──
        // $base = fn () => Siswa::where('kelas_id', $kelas->id);

        // $stats = [
        //     'total'      => $base()->count(),
        //     'aktif'      => $base()->where('status', 'Activated')->count(),
        //     'nonaktif'   => $base()->where('status', 'Deactivated')->count(),
        //     'sekretaris' => $base()->where('sekretaris', 'yes')->count(),
        //     'bendahara'  => $base()->where('bendahara',  'yes')->count(),
        //     'osis'       => $base()->where('osis',       'yes')->count(),
        // ];

        $stats = Siswa::where('kelas_id', $kelas->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Activated' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'Deactivated' THEN 1 ELSE 0 END) as nonaktif,
                SUM(CASE WHEN sekretaris = 'yes' THEN 1 ELSE 0 END) as sekretaris,
                SUM(CASE WHEN bendahara = 'yes' THEN 1 ELSE 0 END) as bendahara,
                SUM(CASE WHEN osis = 'yes' THEN 1 ELSE 0 END) as osis
            ")
            ->first();

        return Inertia::render('Guru/Walas/Index', [
            'kelas'   => $kelas->only(['id', 'kelas']),
            'guru'    => $guru->only(['id', 'nama_lengkap', 'jabatan']),
            'siswa'   => $siswa,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'status', 'role']),
        ]);
    }

    // ─────────────────────────────────────────────
    //  Edit — tampilkan form (opsional, bisa pakai modal)
    // ─────────────────────────────────────────────

    public function edit(Siswa $siswa)
    {
        $guru  = $this->resolveGuru();
        $kelas = $this->resolveKelas($guru);

        abort_if(! $kelas, 403, 'Anda bukan wali kelas.');
        $this->authorizeStudent($siswa, $kelas);

        // Kembalikan data siswa untuk modal/form di frontend
        return response()->json([
            'siswa' => $siswa->only([
                'id', 'nama_lengkap', 'nis', 'nisn', 'id_siswa', 'status', 'sekretaris', 'bendahara', 'osis',
            ]),
        ]);
    }

    // ─────────────────────────────────────────────
    //  Update
    // ─────────────────────────────────────────────

    public function update(Request $request, Siswa $siswa)
    {
        $guru  = $this->resolveGuru();
        $kelas = $this->resolveKelas($guru);

        abort_if(! $kelas, 403, 'Anda bukan wali kelas.');
        $this->authorizeStudent($siswa, $kelas);

        // Validasi — wali kelas hanya boleh ubah field tertentu
        $validated = $request->validate([
            'status'     => ['required', Rule::in(['Activated', 'Deactivated'])],
            'sekretaris' => ['required', Rule::in(['yes', 'no'])],
            'bendahara'  => ['required', Rule::in(['yes', 'no'])],
            'osis'       => ['required', Rule::in(['yes', 'no'])],
        ]);

        // Pastikan hanya 1 sekretaris & 1 bendahara per kelas
        if ($validated['sekretaris'] === 'yes') {
            Siswa::where('kelas_id', $kelas->id)
                 ->where('id', '!=', $siswa->id)
                 ->update(['sekretaris' => 'no']);
        }

        if ($validated['bendahara'] === 'yes') {
            Siswa::where('kelas_id', $kelas->id)
                 ->where('id', '!=', $siswa->id)
                 ->update(['bendahara' => 'no']);
        }

        $siswa->update($validated);

        $queryStr = http_build_query(array_filter($request->only(['search', 'status', 'role', 'page'])));

        return redirect()
            ->to(route('guru.walas.index') . ($queryStr ? '?' . $queryStr : ''))
            ->with('success', "Data {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    // ─────────────────────────────────────────────
    //  Destroy
    // ─────────────────────────────────────────────

    public function destroy(Request $request, Siswa $siswa)
    {
        $guru  = $this->resolveGuru();
        $kelas = $this->resolveKelas($guru);

        abort_if(! $kelas, 403, 'Anda bukan wali kelas.');
        $this->authorizeStudent($siswa, $kelas);

        $nama = $siswa->nama_lengkap;

        DB::transaction(function () use ($siswa) {
            $siswa->delete();
        });

        $queryStr = http_build_query(array_filter($request->only(['search', 'status', 'role', 'page'])));

        return redirect()
            ->to(route('guru.walas.index') . ($queryStr ? '?' . $queryStr : ''))
            ->with('success', "Siswa {$nama} berhasil dihapus.");
    }
}
