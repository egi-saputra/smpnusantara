<?php

namespace App\Http\Middleware;

use App\Models\ProfilSekolah;
use App\Models\Pesan;
use App\Models\Pengumuman;
use App\Models\Assignment;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $profil = cache()->remember('profil_sekolah', now()->addWeek(), fn() => ProfilSekolah::first());
        $user          = Auth::user();
        $isUjianRoute  = $request->routeIs('siswa.ujian.*');

        // ── Guru flags (skip saat ujian) ──────────────────────────
        $guru           = null;
        $isWalas        = false;

        if ($user && !$isUjianRoute) {
            $guru = $user->guru;

            if ($guru) {
                $isWalas = Cache::remember(
                    "guru:{$guru->id}:is_walas", 300,
                    fn () => $guru->kelas()->exists()
                );
            }
        }

        // ── Siswa data ────────────────────────────────────────────
        $siswaData = null;
        $userClass = null;
        $kelasId   = null;

        if ($user) {
            $siswaData = $user->siswa;

            if ($siswaData) {
                $userClass = Cache::remember(
                    "siswa:{$user->id}:kelas_nama", 300,
                    fn () => $siswaData->kelas?->kelas ?? null
                );

                $kelasId = $siswaData->kelas_id
                    ? (int) $siswaData->kelas_id
                    : null;
            }
        }

        // ── Pengumuman (fitur lama, tetap dipertahankan) ──────────
        $announcements = function () use ($user, $isUjianRoute, $kelasId) {
            if (!$user) return collect();

            $role    = strtolower($user->role ?? 'siswa');
            $ttl     = $isUjianRoute ? 300 : 60;
            $cacheKey = "pengumuman:role:{$role}:kelas:{$kelasId}";

            return Cache::remember($cacheKey, $ttl, function () use ($role, $kelasId) {
                return Pengumuman::latest()
                    ->get()
                    ->filter(function ($item) use ($role, $kelasId) {
                        if ($item->penerima === 'semua')   return true;
                        if ($item->penerima === $role)     return true;

                        if ($role === 'siswa' && $item->penerima === 'siswa') {
                            return $item->kelas_id
                                ? $item->kelas_id == $kelasId
                                : true;
                        }

                        return false;
                    })
                    ->values();
            });
        };

        return array_merge(parent::share($request), [

            'auth' => [
                'user'   => $user,
                'avatar' => $user?->avatar,
                'role'   => $user?->role,
            ],

            'siswa' => fn () => $siswaData,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            'logoUrl' => $profil?->file_path
            ? asset('storage/' . $profil->file_path)
            : asset('images/default.png'),

            'namaSekolah' => $profil?->nama_sekolah ?? 'Lumi Platforms, Inc.',

            'profilSekolah' => [
                'namaSekolah' => $profil?->nama_sekolah ?? 'Lumi Platforms, Inc.',
                'alamat' => $profil?->alamat ?? 'Jl. Raya Citayam - Parung RT. 002 / RW. 011 Desa Ragajaya, Kecamatan Bojonggede, Kabupaten Bogor, Jawa Barat 16920.',
                'telepon' => $profil?->telepon ?? '+62 XXX-XXXX-XXX',
                'email' => $profil?->email ?? 'info@lumiverse.co.id',
                'website' => $profil?->website ?? 'www.lumiverse.co.id',
                'visi' => $profil?->visi ?? 'Menjadi sekolah kejuruan berstandar nasional yang mencetak lulusan kompeten, berintegritas, dan berdaya saing global.',
                'misi' => $profil?->misi ?? 'Menyelenggarakan pendidikan vokasi berkualitas dengan kurikulum berbasis industri, didukung tenaga pengajar profesional dan fasilitas modern.',
            ],

            'unreadAssignmentCount' => function () {
                if (!Auth::check()) return 0;
                $guru = Guru::where('user_id', Auth::id())->first();
                if (!$guru) return 0;
                return Assignment::where('guru_id', $guru->id)
                    ->where('is_read', false)
                    ->count();
            },

            'announcements' => $announcements,
            'userClass' => $userClass,

            // BUG FIX: hanya ada SATU 'kelasId' — dari siswa relation (sudah benar di atas)
            'kelasId' => $kelasId,
            'isWalas'        => $isWalas,

            // ── Pesan untuk bell notifikasi ───────────────────────
            'pesan' => fn () => $this->loadPesanForUser($user, $kelasId),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  PRIVATE HELPER
    // ─────────────────────────────────────────────────────────

    /**
     * Muat pesan yang relevan untuk user.
     *
     * @param  mixed    $user     Auth user instance atau null
     * @param  int|null $kelasId  kelas_id siswa (sudah dihitung di share())
     */
    private function loadPesanForUser($user, ?int $kelasId): array
    {
        if (! $user) return [];

        return Pesan::with('kelas:id,kelas')
            ->select(['id', 'judul', 'isi', 'penerima', 'kelas_id', 'pengirim_id', 'created_at'])
            ->where(function ($q) use ($user, $kelasId) {
                // Pesan untuk semua
                $q->where('penerima', 'semua')

                // Pesan untuk role user ini (broadcast tanpa filter kelas)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('penerima', $user->role)
                         ->whereNull('kelas_id');
                  });

                // Pesan untuk siswa kelas tertentu
                if ($user->role === 'siswa' && $kelasId) {
                    $q->orWhere(function ($q2) use ($kelasId) {
                        $q2->where('penerima', 'siswa')
                           ->where('kelas_id', $kelasId);
                    });
                }
            })
            ->latest()
            ->limit(50)
            ->get()
            ->toArray();
    }
}
