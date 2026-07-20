<?php

namespace App\Observers;

use App\Models\Kelas;
use Illuminate\Support\Facades\Cache;

class KelasObserver
{
    /**
     * Setiap kali kelas dibuat / wali kelasnya diganti / kelas dihapus,
     * cache "is_walas" milik guru yang terkait harus di-invalidate,
     * supaya perubahan langsung terlihat tanpa nunggu TTL 5 menit habis.
     */
    public function created(Kelas $kelas): void
    {
        $this->forgetWalasCache($kelas->guru_id);
    }

    public function updated(Kelas $kelas): void
    {
        // Kalau guru_id berubah, invalidate baik guru lama maupun guru baru
        if ($kelas->wasChanged('guru_id')) {
            $this->forgetWalasCache($kelas->getOriginal('guru_id'));
            $this->forgetWalasCache($kelas->guru_id);
        }
    }

    public function deleted(Kelas $kelas): void
    {
        $this->forgetWalasCache($kelas->guru_id);
    }

    private function forgetWalasCache(?int $guruId): void
    {
        if ($guruId) {
            Cache::forget("guru:{$guruId}:is_walas");
        }
    }
}